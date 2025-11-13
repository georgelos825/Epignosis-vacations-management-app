# Vacations Management App  
**PHP 8.4 + React + PostgreSQL 18**  

# Tech Stack

### **Backend**
- PHP 8.4 (plain PHP, no frameworks)
- FastRoute
- Firebase JWT (HS256)
- PostgreSQL 18 (tested 14+)
- Composer
- PHPUnit

## PHP Configuration
This project runs with the default php.ini (development) settings.  
Required extensions:
- curl
- mbstring
- openssl
- pdo_pgsql
- pgsql

### **Frontend**
- React + Vite
- Plain CSS + inline styles

### **Database**
- PostgreSQL 18
- Schema + seed provided

### **Docker**
3 containers:
- `db` → Postgres 18  
- `api` → PHP 8.4 + Apache  
- `web` → nginx serving React build

# Entities Overview

### **users**
- `id` (UUID)
- `name`
- `email` (unique)
- `employee_code` (unique, exactly 7 digits)
- `password_hash` (bcrypt)
- `role` (`manager` | `employee`)
- `current_jti` (nullable)
- timestamps

### **vacation_requests**
- `id`
- `user_id` (FK → users)
- `start_date`
- `end_date`
- `reason` (nullable)
- `status` (`pending` | `approved` | `rejected`)
- `submitted_at` (timestamptz)

# Security & Authentication

### Password Hashing
password_hash($password, PASSWORD_BCRYPT)

### JWT (HS256)
Claims include:
- iss, aud, iat, nbf, exp  
- sub (user_id)  
- role  
- email  
- jti (unique per active session)

Secret: `JWT_SECRET`

### **Single Active Token per User (JTI enforcement)**
On login:
- new random `jti`
- stored to `users.current_jti`
- token issued with same jti

Every request:
- decode JWT
- verify signature
- verify `jwt.jti === users.current_jti`
- else → `401 {"error":"Token invalidated"}`

On logout:
- clear `current_jti`

### Role-Based Authorization
- all routes except `POST /api/auth/login` require JWT
- manager-only routes enforced via `UserService::ensureManager`
- RequestService enforces:
  - only employees create requests
  - only pending can be canceled
  - only managers approve/reject
  - employees only see own requests

# Backend Validation Rules

### Create User (manager-only)
Required: `name`, `email`, `employee_code`, `password`, `role`

- email: must be valid
- password: ≥9 chars, no whitespace
- employee_code: exactly 7 digits (`^\d{7}$`)
- role: manager | employee
- unique email + unique employee_code

Errors:
- 422 → validation
- 409 → unique violation

### Update User (manager-only)
- email → valid format
- employee_code → **cannot change**
- role → allowed values
- password: ≥9 chars, no whitespace

# Vacation Request Rules

- only employees can create
- start_date and end_date required
- server and client enforce: `start_date <= end_date`
- cancel only own + pending
- manager sees all requests
- manager approves/rejects

# Local Setup (WITHOUT Docker)

## 1. Prerequisites
- PHP 8.4 CLI  
- Composer  
- Node.js ≥ 18  
- PostgreSQL 18 

## 2. Create DB

CREATE DATABASE vacations;

Then from repo vacationsEpignosis\db: inside your query tool run schema.sql and then seed.sql

Default users:
- manager@example.com / **manager123**
- employee@example.com / **employee123**

## 3. Backend Configuration
Create `vacationsEpignosis/.env`:

DB_HOST=localhost
DB_PORT=5432
DB_NAME=vacations
DB_USER=postgres
DB_PASS=postgres

JWT_SECRET=supersecret
JWT_ISS=vacations-api
JWT_AUD=vacations-client
JWT_TTL_MINUTES=120


## 4. Run Backend

cd vacationsEpignosis
composer install
php -S localhost:8000 -t public


## 5. Run Frontend

cd vacationsEpignosisFront
npm install
npm run dev


Frontend defaults to hitting:  
http://localhost:8000

# Tests (PHPUnit)

## 1. Create Test DB

CREATE DATABASE vacations_test;

Load schema:

psql -U postgres -d vacations_test -f schema.sql


## 2. Run Tests
cd vacationsEpignosis
composer install
php vendor/bin/phpunit

Covered:
- AuthServiceTest: login/logout/JTI invalidation  
- UserRepositoryTest  
- UserServiceTest: validation, update rules  
- RequestServiceTest: employee create, manager processes, cancel rules  

# Dockerized Setup (DB 18 + PHP 8.4 API + Nginx React)

## 1. Prerequisites
- Docker Desktop
- Docker Compose
- Ports free: 5432, 8000, 5173

## 2. Start Containers
docker compose down -v
docker compose up -d --build

Services:
- DB: postgres:18  
- API: php:8.4-apache (port 8000)  
- Web: nginx serving React build (port 5173)

## 3. Initialize DB, do this before login, in the given order(manual)

# Copy SQL files into the db container
docker cp vacationsEpignosis/docker/sql/schema.sql vacations_db:/tmp/schema.sql
docker cp vacationsEpignosis/docker/sql/seed.sql vacations_db:/tmp/seed.sql

# Apply schema and seed inside the container
docker exec -it vacations_db psql -U postgres -d vacations -f /tmp/schema.sql
docker exec -it vacations_db psql -U postgres -d vacations -f /tmp/seed.sql

## 4. App URLs
- Frontend: http://localhost:5173  
- API: http://localhost:8000  

Manager login:
manager@example.com / manager123

Employee login:
employee@example.com / employee123

# Role-Based Authorization Summary

### Public
- **POST /api/auth/login**

### Authenticated
- **POST /api/auth/logout**

### Manager-only
- GET /api/users  
- POST /api/users  
- PUT /api/users/{id}  
- DELETE /api/users/{id}  
- GET /api/requests  
- POST /api/requests/{id}/approve  
- POST /api/requests/{id}/reject  

### Employee-only
- GET /api/requests (own only)  
- POST /api/requests  
- DELETE /api/requests/{id} (own & pending only)

Authorization pipeline:
1. `index.php` extracts Bearer token  
2. verifies signature + expiration  
3. checks jti matches DB  
4. controllers receive `$claims`  
5. services enforce role/ownership rules  

# Frontend Behaviour

### Login Flow
- token + role saved in localStorage  
- redirects to /manager or /employee

### Manager UI
- CRUD users  
- view all vacation requests  
- approve/reject  

### Employee UI
- create vacation request  
- see own requests  
- cancel pending requests  

### 401 Handling
If backend returns 401 ("Token invalidated"):
- clear storage
- redirect to "/"

# Troubleshooting

### Login fails
Check DB seeded:
docker compose exec db psql -U postgres -d vacations -c "SELECT email FROM users;"

If empty → reapply schema+seed.

### API cannot connect to DB
Verify docker-compose values:

DB_HOST=db
DB_PORT=5432
DB_NAME=vacations
DB_USER=postgres
DB_PASS=postgres

### Tests failing
Ensure test DB:
CREATE DATABASE vacations_test;

Then:
psql -U postgres -d vacations_test -f schema.sql

### JTI testing tip
1. Login → token A  
2. Login again → token B  
3. Use token A → **401 Token invalidated**


