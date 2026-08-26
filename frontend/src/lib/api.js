import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
})

export function formatApiError(e, fallback) {
  const data = e?.response?.data

  if (typeof data === 'string' && data.trim()) return data
  if (data?.message) return data.message

  if (data && typeof data === 'object') {
    try {
      return JSON.stringify(data)
    } catch {}
  }

  return e?.message || fallback || 'Request failed'
}

export function setAuthToken(token) {
  if (token) {
    api.defaults.headers.common.Authorization = `Bearer ${token}`
  } else {
    delete api.defaults.headers.common.Authorization
  }
}

export default api
