import React, { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { api } from '../api/client'

export default function Login(){
  const [email, setEmail] = useState('manager@example.com')
  const [password, setPassword] = useState('manager123')
  const [err, setErr] = useState('')
  const nav = useNavigate()

  const submit = async (e)=>{
    e.preventDefault()
    try{
      const res = await api.login(email,password)
      api.setToken(res.token); api.setRole(res.role)
      nav(res.role==='manager'?'/manager':'/employee')
    }catch(ex){ setErr(ex?.data?.error || 'Login failed') }
  }

  return (
    <form onSubmit={submit} style={{display:'grid', gap:12}}>
      <h2>Sign in</h2>
      <input value={email} onChange={e=>setEmail(e.target.value)} placeholder="email"/>
      <input type="password" value={password} onChange={e=>setPassword(e.target.value)} placeholder="password"/>
      <button>Login</button>
      {err && <div style={{color:'tomato'}}>{err}</div>}
    </form>
  )
}
