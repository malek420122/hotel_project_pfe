<template>
  <div class="relative">
    <button @click="open = !open" class="relative p-2 rounded-xl hover:bg-gray-100 transition-colors">
      <span class="text-xl">🔔</span>
      <span v-if="notifStore.unreadCount > 0"
        class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
        {{ notifStore.unreadCount > 9 ? '9+' : notifStore.unreadCount }}
      </span>
    </button>
    <div v-if="open" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border z-50 overflow-hidden">
      <div class="p-4 border-b flex justify-between items-center">
        <h4 class="font-bold text-gray-800">Notifications</h4>
        <button @click="notifStore.markAllRead()" class="text-xs text-secondary hover:underline">Tout lire</button>
      </div>
      <div class="max-h-80 overflow-y-auto">
        <div v-if="!notifStore.notifications.length" class="p-6 text-center text-gray-400">
          <p class="text-2xl mb-2">🔔</p>
          <p class="text-sm">Aucune notification</p>
        </div>
        <div v-for="notif in notifStore.notifications.slice(0,8)" :key="notif._id || notif.id"
          @click="notifStore.markRead(notif._id || notif.id)"
          :class="['flex items-start gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b last:border-0', !notif.estLue ? 'bg-blue-50' : '']">
          <span class="text-xl flex-shrink-0">{{ notif.icon || '📬' }}</span>
          <div>
            <p class="text-sm font-medium text-gray-800">{{ notif.titre }}</p>
            <p class="text-xs text-gray-500">{{ notif.message }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ notif.date }}</p>
          </div>
          <div v-if="!notif.estLue" class="w-2 h-2 bg-secondary rounded-full flex-shrink-0 mt-1"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useNotifStore } from '../stores/notifications'

const notifStore = useNotifStore()
const open = ref(false)

onMounted(() => notifStore.fetchNotifs())
</script>
