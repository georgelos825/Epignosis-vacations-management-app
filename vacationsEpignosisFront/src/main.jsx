// src/main.jsx
import React from 'react'
import { createRoot } from 'react-dom/client'
import { createBrowserRouter, RouterProvider } from 'react-router-dom'

// must load BEFORE the app so all tabs sync tokens
import './session-sync';

import App from './App'
import Login from './pages/Login'
import Manager from './pages/Manager'
import Employee from './pages/Employee'
import Protected from './parts/Protected'

const router = createBrowserRouter([
  { 
    path: '/', 
    element: <App />,
    children: [
      { index: true, element: <Login/> },
      { 
        path: 'manager', 
        element: (
          <Protected role="manager">
            <Manager/>
          </Protected>
        ) 
      },
      { 
        path: 'employee', 
        element: (
          <Protected role="employee">
            <Employee/>
          </Protected>
        ) 
      },
    ]
  }
])

createRoot(document.getElementById('root')).render(
  <RouterProvider router={router} />
)
