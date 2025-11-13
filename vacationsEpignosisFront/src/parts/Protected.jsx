import React from 'react'
import { Navigate } from 'react-router-dom'
import { api } from '../api/client'

export default function Protected({ role, children }){
  const token = api.getToken()
  const myRole = api.getRole()
  if (!token) return <Navigate to="/" replace/>
  if (role && myRole !== role) return <Navigate to={`/${myRole}`} replace/>
  return children
}
