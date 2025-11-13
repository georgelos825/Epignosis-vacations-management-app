const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';
export function getToken(){ return localStorage.getItem('token') }
export function setToken(t){ localStorage.setItem('token', t) }
export function clearToken(){ localStorage.removeItem('token'); localStorage.removeItem('role') }
export function getRole(){ return localStorage.getItem('role') }
export function setRole(r){ localStorage.setItem('role', r) }

async function request(path, options = {}) {
     const headers = options.headers || {};
     headers['Content-Type'] = 'application/json';
     const token = getToken();
     if (token) headers['Authorization'] = 'Bearer ' + token;
     const res = await fetch(API_URL + path, { ...options, headers });
     const text = await res.text();
     let json;
     try { json = text ? JSON.parse(text) : null; } catch(_) { json = { raw: text }; }
     if (!res.ok) {
      if (res.status === 401) {
         // π.χ. "Token invalidated" από το backend -> καθάρισμα + γυρνάμε login
         clearToken();
         try { window.location.assign('/'); } catch {}
       }
       throw { status: res.status, data: json };
     }
     return json;
   }

export const api = {
  API_URL, getToken, setToken, clearToken, getRole, setRole,
  login: (email,password)=>request('/api/auth/login',{method:'POST', body: JSON.stringify({email,password})}),
  logout: () => request('/api/auth/logout', { method: 'POST' }),
  listUsers: ()=>request('/api/users'),
  createUser: (u)=>request('/api/users',{method:'POST', body: JSON.stringify(u)}),
  updateUser: (id,u)=>request('/api/users/'+id,{method:'PUT', body: JSON.stringify(u)}),
  deleteUser: (id)=>request('/api/users/'+id,{method:'DELETE'}),
  listRequests: ()=>request('/api/requests'),
  createRequest: (r)=>request('/api/requests', {method:'POST', body: JSON.stringify(r)}),
  deleteRequest: (id)=>request('/api/requests/'+id,{method:'DELETE'}),
  approveRequest: (id)=>request('/api/requests/'+id+'/approve',{method:'POST'}),
  rejectRequest: (id)=>request('/api/requests/'+id+'/reject',{method:'POST'}),
};

export function getErrorMessages(ex) {
  const d = ex?.data ?? {};
  if (Array.isArray(d.errors) && d.errors.length) return d.errors;
  if (typeof d.error === 'string' && d.error) return [d.error];
  if (typeof d === 'string' && d) return [d];
  return ['Something went wrong'];
}
