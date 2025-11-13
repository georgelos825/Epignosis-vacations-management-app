<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

use App\{Database,Auth,Helpers};
use App\Controller\{AuthController,UsersController,RequestsController};
use App\Service\{AuthService,UserService,RequestService};
use App\Repository\{UserRepository,RequestRepository};
use Dotenv\Dotenv;

if (file_exists(__DIR__.'/../.env')) { Dotenv::createImmutable(__DIR__.'/..')->load(); }

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

$db = new Database(
    Helpers::requireEnv('DB_HOST'),
    (int)Helpers::requireEnv('DB_PORT'),
    Helpers::requireEnv('DB_NAME'),
    Helpers::requireEnv('DB_USER'),
    Helpers::requireEnv('DB_PASS')
);
$pdo=$db->pdo();
$auth = new Auth(
    Helpers::requireEnv('JWT_SECRET'),
    getenv('JWT_ISS')?:'vacations-api',
    getenv('JWT_AUD')?:'vacations-client',
    (int)(getenv('JWT_TTL_MINUTES')?:120)
);

// DI wiring
$userRepo = new UserRepository($pdo);
$reqRepo  = new RequestRepository($pdo);
$authSvc  = new AuthService($userRepo, $auth);
$userSvc  = new UserService($userRepo);
$reqSvc   = new RequestService($reqRepo);
$authCtrl = new AuthController($authSvc);
$usersCtrl = new UsersController($userSvc);
$requestsCtrl = new RequestsController($reqSvc);

function claims($auth, $userRepo) {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if(str_starts_with($h,'Bearer ')){
        $jwt=substr($h,7);
        try {
            $c = $auth->verify($jwt); // includes 'sub' and 'jti'
            $dbJti = $userRepo->getCurrentJti($c['sub']);
             // if user has no active session or jti mismatch => invalid
            if (!$dbJti || $dbJti !== ($c['jti'] ?? null)) {
                App\Helpers::json(401, ['error'=>'Token invalidated']);
            }
            return $c;
        } catch (Throwable $e) {
            App\Helpers::json(401, ['error'=>'Invalid token']);
        }
    }
    return [];
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r){
    $r->addRoute('POST','/api/auth/login',['AuthController','login']);
    $r->addRoute('GET','/api/users',['UsersController','list']);
    $r->addRoute('POST','/api/users',['UsersController','create']);
    $r->addRoute('PUT','/api/users/{id}',['UsersController','update']);
    $r->addRoute('DELETE','/api/users/{id}',['UsersController','delete']);
    $r->addRoute('GET','/api/requests',['RequestsController','list']);
    $r->addRoute('POST','/api/requests',['RequestsController','create']);
    $r->addRoute('DELETE','/api/requests/{id}',['RequestsController','delete']);
    $r->addRoute('POST','/api/requests/{id}/approve',['RequestsController','approve']);
    $r->addRoute('POST','/api/requests/{id}/reject',['RequestsController','reject']);
    $r->addRoute('POST','/api/auth/logout',['AuthController','logout']);
});

$method=$_SERVER['REQUEST_METHOD'];
$uri=$_SERVER['REQUEST_URI'];
if(false!==$pos=strpos($uri,'?')) $uri=substr($uri,0,$pos);
$uri=rawurldecode($uri);
$route=$dispatcher->dispatch($method,$uri);

switch($route[0]){
    case FastRoute\Dispatcher::NOT_FOUND: Helpers::json(404,['error'=>'Not Found']); break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED: Helpers::json(405,['error'=>'Method Not Allowed']); break;
    case FastRoute\Dispatcher::FOUND:
        [$cls,$m] = $route[1]; $vars=$route[2];
        $map = [
            'AuthController'=>$authCtrl,
            'UsersController'=>$usersCtrl,
            'RequestsController'=>$requestsCtrl,
        ];
        $controller = $map[$cls] ?? null;
        if(!$controller) Helpers::json(500,['error'=>'Bad route handler']);
        // Require auth except login
        $c=[];
        if(!($controller instanceof App\Controller\AuthController && $m==='login')){
            $c=claims($auth, $userRepo);
            if(!$c) Helpers::json(401,['error'=>'Unauthorized']);
        }
        $ref=new ReflectionMethod($controller,$m);
        $args=[];
        foreach($ref->getParameters() as $p){
            $n=$p->getName();
            if($n==='claims') $args[]=$c;
            elseif(isset($vars[$n])) $args[]=$vars[$n];
            else $args[]=null;
        }
        $ref->invokeArgs($controller,$args);
        break;
}
