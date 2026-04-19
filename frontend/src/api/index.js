import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: false,
})

// Request interceptor: attach token
api.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    const lang = localStorage.getItem('hotelease_locale') || 'fr'
    if (token) config.headers.Authorization = `Bearer ${token}`
    config.headers['Accept-Language'] = lang
    return config
  },
  error => Promise.reject(error)
)

// Response interceptor: handle 401
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
