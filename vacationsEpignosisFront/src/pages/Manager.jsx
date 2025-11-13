import React, { useEffect, useState } from 'react'
import { api, getErrorMessages } from '../api/client'

function CreateUser({ onCreated }){
  const [form, setForm] = useState({ name:'', email:'', employee_code:'', password:'', role:'employee' })
  const [errs, setErrs] = useState([])

  return (
    <div style={{border:'1px solid #ddd', padding:12, borderRadius:8}}>
      <h3>Create user</h3>

      {/* errors list */}
      {errs.length > 0 && (
        <div style={{background:'#ffecec', border:'1px solid #f5a9a9', padding:8, borderRadius:6, marginBottom:8}}>
          <ul style={{margin:0, paddingLeft:18}}>
            {errs.map((e,i)=><li key={i}>{e}</li>)}
          </ul>
        </div>
      )}

      <div style={{display:'grid', gridTemplateColumns:'repeat(5, 1fr)', gap:8}}>
        {['name','email','employee_code','password'].map(k=>(
          <input
            key={k}
            placeholder={k}
            value={form[k]}
            onChange={e=>setForm({...form,[k]:e.target.value})}
          />
        ))}
        <select value={form.role} onChange={e=>setForm({...form, role:e.target.value})}>
          <option value="employee">employee</option>
          <option value="manager">manager</option>
        </select>
      </div>

      <div style={{marginTop:8}}>
        <button onClick={async()=>{
          try{
            setErrs([]);
            const u = await api.createUser(form);
            onCreated(u);
            // optional: καθάρισε τη φόρμα
            setForm({ name:'', email:'', employee_code:'', password:'', role:'employee' });
          }catch(ex){
            setErrs(getErrorMessages(ex));  // <- παίρνει ["...","..."]
          }
        }}>Create</button>
      </div>
    </div>
  )
}

function RequestsModeration(){
  const [items, setItems] = useState([])
  const [error, setError] = useState('')

  const load = async()=>{
    try {
      setError('');
      setItems(await api.listRequests())
    } catch(ex){
      setError(ex?.data?.error || 'Error')
    }
  }

  useEffect(()=>{ load() },[])

  const act = async(id,kind)=>{
    try{
      if(kind==='approve') await api.approveRequest(id);
      else await api.rejectRequest(id);

      await load();
    }catch(ex){
      alert(ex?.data?.error || 'Error');
    }
  }

  return (
    <div style={{border:'1px solid #ddd', padding:12, borderRadius:8}}>
      <h3>All requests</h3>
      {error && <div style={{color:'tomato'}}>{error}</div>}

      <table style={{width:'100%'}}>
        <thead>
          <tr><th>Employee</th><th>Dates</th><th>Reason</th><th>Status</th><th>Submitted</th><th/></tr>
        </thead>
        <tbody>
  {items.map(r => (
    <tr key={r.id}>
      <td>{r.employee_name}<div style={{opacity:.7}}>{r.employee_email}</div></td>
      <td>{r.start_date} → {r.end_date}</td>
      <td>{r.reason || '-'}</td>
      <td>{r.status}</td>
      <td>{new Date(r.submitted_at).toLocaleString()}</td>
      <td style={{textAlign:'right'}}>
        <button onClick={() => act(r.id,'approve')}>Approve</button>{' '}
        <button onClick={() => act(r.id,'reject')}>Reject</button>
      </td>
    </tr>
  ))}
</tbody>
      </table>
    </div>
  )
}

export default function Manager(){
  const [users, setUsers] = useState([])
  const [error, setError] = useState('')

  const load = async()=>{
    try {
      setError('');
      setUsers(await api.listUsers());
    } catch(ex){
      setError(ex?.data?.error || 'Error');
    }
  }

  useEffect(()=>{ load() },[])

  async function onEdit(u) {
    const name = prompt('New name:', u.name);
    if (name === null) return;

    const email = prompt('New email:', u.email);
    if (email === null) return;

    const password = prompt('New password (leave blank to keep):');

    const payload = {};
    if (name && name !== u.name) payload.name = name;
    if (email && email !== u.email) payload.email = email;
    if (password) payload.password = password;

    if (Object.keys(payload).length === 0) return;

    try {
      await api.updateUser(u.id, payload);
      await load();
      alert('User updated');
    } catch (ex) {
      alert(ex?.data?.error || 'Update failed');
      try { await load(); } catch {}
    }
  }

  return (
    <div style={{display:'grid', gap:12}}>
      <div>
        <h2>Users</h2>
        {error && <div style={{color:'tomato'}}>{error}</div>}

        <table style={{width:'100%'}}>
          <thead>
            <tr><th>Name</th><th>Email</th><th>Code</th><th>Role</th><th/></tr>
          </thead>
          <tbody>
            {users.map(u=>(
              <tr key={u.id}>
                <td>{u.name}</td>
                <td>{u.email}</td>
                <td><code>{u.employee_code}</code></td>
                <td>{u.role}</td>
                <td style={{textAlign:'right'}}>
                  <button onClick={()=>onEdit(u)}>Edit</button>{' '}
                  <button onClick={async()=>{
                    if (confirm('Delete user?')){
                      try {
                        await api.deleteUser(u.id);
                        await load();
                      } catch (ex) {
                        alert(ex?.data?.error || 'Delete failed');
                        try { await load(); } catch {}
                      }
                    }
                  }}>Delete</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <CreateUser onCreated={(u)=>setUsers([u, ...users])}/>
      <RequestsModeration/>
    </div>
  )
}
