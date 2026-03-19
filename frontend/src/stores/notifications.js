import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'

export const useNotifStore = defineStore('notifications', () => {
  const notifications = ref([])

  const unreadCount = computed(() => notifications.value.filter(n => !n.estLue).length)

  async function fetchNotifs() {
    try {
      const { data } = await api.get('/notifications')
      notifications.value = data
    } catch {}
  }

  async function markRead(id) {
    try {
      await api.put(`/notifications/${id}/lire`)
      const n = notifications.value.find(n => n._id === id)
      if (n) n.estLue = true
    } catch {}
  }

  async function markAllRead() {
    try {
      await api.put('/notifications/lire-toutes')
      notifications.value.forEach(n => n.estLue = true)
    } catch {}
  }

  return { notifications, unreadCount, fetchNotifs, markRead, markAllRead }
})
