import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isClient = computed(() => user.value?.role === 'client')
  const isReceptionniste = computed(() => user.value?.role === 'receptionniste')
  const isMarketing = computed(() => user.value?.role === 'marketing')

  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)
    token.value = data.access_token || data.token
    user.value = data.user
    localStorage.setItem('token', token.value)
    localStorage.setItem('user', JSON.stringify(user.value))
    return data.user
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    token.value = data.access_token || data.token
    user.value = data.user
    localStorage.setItem('token', token.value)
    localStorage.setItem('user', JSON.stringify(user.value))
    return data.user
  }

  async function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    try { api.post('/auth/logout') } catch {}
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const { data } = await api.get('/auth/me')
      user.value = data
      localStorage.setItem('user', JSON.stringify(data))
    } catch { await logout() }
  }

  function updateUser(data) {
    user.value = { ...user.value, ...data }
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  return { token, user, isAuthenticated, isAdmin, isClient, isReceptionniste, isMarketing, login, register, logout, fetchMe, updateUser }
})
