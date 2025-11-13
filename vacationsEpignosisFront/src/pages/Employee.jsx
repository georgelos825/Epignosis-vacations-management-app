import React, { useEffect, useState } from 'react'
import { api, getErrorMessages } from '../api/client'

function CreateRequest({ onCreated }) {
  const [form, setForm] = useState({ start_date: '', end_date: '', reason: '' })
  const [error, setError] = useState('')
  const start = form.start_date ? new Date(form.start_date) : null
  const end   = form.end_date ? new Date(form.end_date) : null
  const invalidRange = start && end && start > end

  function onStartChange(v) {
    // if new start > end, push end = start
    if (form.end_date && v && new Date(v) > new Date(form.end_date)) {
      setForm(f => ({ ...f, start_date: v, end_date: v }))
    } else {
      setForm(f => ({ ...f, start_date: v }))
    }
  }

  async function submit() {
    if (!form.start_date || !form.end_date) {
      setError('Both dates are required')
      return
    }
    if (invalidRange) {
      setError('start_date cannot be after end_date')
      return
    }
    try {
      setError('')
      const created = await api.createRequest(form)
      onCreated(created)
      setForm({ start_date: '', end_date: '', reason: '' })
    } catch (ex) {
      setError(getErrorMessages(ex).join(', '))
    }
  }

  return (
    <div style={{border:'1px solid #ddd', padding:12, borderRadius:8}}>
      <h3>Create vacation request</h3>
      <div style={{display:'grid', gridTemplateColumns:'repeat(3, 1fr)', gap:8}}>
        <input
          type="date"
          value={form.start_date}
          onChange={e => onStartChange(e.target.value)}
          aria-label="start date"
        />
        {/* end cannot be before start */}
        <input
          type="date"
          value={form.end_date}
          min={form.start_date || undefined}
          onChange={e => setForm(f => ({ ...f, end_date: e.target.value }))}
          aria-label="end date"
        />
        <input
          placeholder="reason (optional)"
          value={form.reason}
          onChange={e => setForm(f => ({ ...f, reason: e.target.value }))}
        />
      </div>

      {invalidRange && (
        <div style={{color:'tomato', marginTop:8}}>
          start_date cannot be after end_date
        </div>
      )}
      {error && (
        <div style={{color:'tomato', marginTop:8}}>
          {error}
        </div>
      )}

      <div style={{marginTop:8}}>
        <button disabled={invalidRange || !form.start_date || !form.end_date} onClick={submit}>
          Request
        </button>
      </div>
    </div>
  )
}

export default function Employee() {
  const [items, setItems] = useState([])
  const [error, setError] = useState('')

  const load = async () => {
    try {
      setError('')
      setItems(await api.listRequests())
    } catch (ex) {
      setError(getErrorMessages(ex).join(', '))
    }
  }

  useEffect(() => { load() }, [])

  const cancelReq = async (id) => {
    if (!confirm('Cancel this request?')) return
    try {
      await api.deleteRequest(id)
      await load()
    } catch (ex) {
      alert(getErrorMessages(ex).join('\n'))
      try { await load() } catch {}
    }
  }

  return (
    <div style={{display:'grid', gap:12}}>
      <CreateRequest onCreated={() => load()} />

      <div style={{border:'1px solid #ddd', padding:12, borderRadius:8}}>
        <h3>My requests</h3>
        {error && <div style={{color:'tomato'}}>{error}</div>}
        <table style={{width:'100%'}}>
          <thead>
            <tr>
              <th>Dates</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Submitted</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
  {items.map(r => (
    <tr key={r.id}>
      <td>{r.start_date} → {r.end_date}</td>
      <td>{r.reason || '-'}</td>
      <td>{r.status}</td>
      <td>{new Date(r.submitted_at).toLocaleString()}</td>
      <td style={{textAlign:'right'}}>
        {r.status === 'pending' && (
          <button onClick={() => cancelReq(r.id)}>Cancel</button>
        )}
      </td>
    </tr>
  ))}
</tbody>
        </table>
      </div>
    </div>
  )
}
