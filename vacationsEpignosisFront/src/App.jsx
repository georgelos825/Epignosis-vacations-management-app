import React from 'react'
import { Outlet, useNavigate } from 'react-router-dom'
import { api } from './api/client'

export default function App(){
  const nav = useNavigate()
  const role = api.getRole()
  const logout = async ()=> {
     try { await api.logout(); } catch(_){}
     api.clearToken();
     nav('/');
   } 
  return (
    <div style={{maxWidth:960, margin:'0 auto', padding:24, fontFamily:'system-ui'}}>
      <div style={{display:'flex', gap:12, alignItems:'center'}}>
        <strong>Vacations</strong>
        <div style={{flex:1}}/>
        {role && <span style={{opacity:.75}}>role: {role}</span>}
        {role && <button onClick={logout}>Sign out</button>}
      </div>
      <hr/>
      <Outlet/>
      <div style={{opacity:.6, fontSize:12}}>API: {api.API_URL}</div>
    </div>
  )
}
