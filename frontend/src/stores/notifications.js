import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'

export const useNotifStore = defineStore('notifications', () => {
  const notifications = ref([])
  const pollingId = ref(null)

  const unreadCount = computed(() => notifications.value.filter(n => !n.estLue).length)

  async function fetchNotifs() {
    try {
      const { data } = await api.get('/notifications')
      notifications.value = Array.isArray(data) ? data : []
    } catch {}
  }

  async function markRead(id) {
    try {
      await api.put(`/notifications/${id}/read`)
      const n = notifications.value.find(n => n._id === id)
      if (n) n.estLue = true
    } catch {}
  }

  async function markAllRead() {
    try {
      await api.put('/notifications/read-all')
      notifications.value.forEach(n => n.estLue = true)
    } catch {}
  }

  function startPolling(intervalMs = 30000) {
    if (pollingId.value) return
    pollingId.value = setInterval(() => {
      fetchNotifs()
    }, intervalMs)
  }

  function stopPolling() {
    if (!pollingId.value) return
    clearInterval(pollingId.value)
    pollingId.value = null
  }

  return { notifications, unreadCount, fetchNotifs, markRead, markAllRead, startPolling, stopPolling }
})
