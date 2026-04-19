<template>
  <div class="relative">
    <button @click="open = !open" :class="['dashboard-notif-btn', notifStore.unreadCount > 0 && 'dashboard-notif-shake']">
      <span class="text-xl">🔔</span>
      <span v-if="notifStore.unreadCount > 0"
        class="dashboard-notif-count">
        {{ notifStore.unreadCount > 9 ? '9+' : notifStore.unreadCount }}
      </span>
    </button>
    <div v-if="open" class="dashboard-notif-menu">
      <div class="dashboard-notif-head">
        <h4>Notifications</h4>
        <button @click="notifStore.markAllRead()" class="dashboard-notif-clear">Tout marquer comme lu</button>
      </div>
      <div class="dashboard-notif-list">
        <div v-if="!notifStore.notifications.length" class="dashboard-notif-empty">
          <p class="text-2xl mb-2">🔔</p>
          <p class="text-sm">Aucune notification</p>
        </div>
        <div v-for="notif in notifStore.notifications.slice(0,5)" :key="notif._id || notif.id"
          @click="notifStore.markRead(notif._id || notif.id)"
          :class="['dashboard-notif-item', !notif.estLue && 'dashboard-notif-item-unread']">
          <span class="text-xl flex-shrink-0">{{ notifIcon(notif.type) }}</span>
          <div>
            <p class="dashboard-notif-title">{{ notifTitle(notif.type) }}</p>
            <p class="dashboard-notif-message">{{ notif.message }}</p>
            <p class="dashboard-notif-date">{{ timeAgo(notif.created_at || notif.date) }}</p>
          </div>
          <div v-if="!notif.estLue" class="dashboard-notif-dot"></div>
        </div>
      </div>
      <RouterLink to="/dashboard/client/overview" class="dashboard-notif-footer-link" @click="open = false">
        Voir toutes les notifications
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useNotifStore } from '../stores/notifications'

const notifStore = useNotifStore()
const open = ref(false)

const icons = {
  booking_confirmed: '✅',
  booking_cancelled: '❌',
  checkin_reminder: '⏰',
  review_request: '⭐',
  promotion: '🎁',
  loyalty_points: '🏅',
  RESERVATION_CONFIRMEE: '✅',
  RESERVATION_ANNULEE: '❌',
  PAIEMENT_CONFIRME: '💳',
  CHECKIN: '🏨',
  CHECKOUT: '🧾',
}

const titles = {
  booking_confirmed: 'Réservation confirmée',
  booking_cancelled: 'Réservation annulée',
  checkin_reminder: 'Rappel de check-in',
  review_request: 'Demande d avis',
  promotion: 'Nouvelle promotion',
  loyalty_points: 'Points fidélité',
  RESERVATION_CONFIRMEE: 'Réservation confirmée',
  RESERVATION_ANNULEE: 'Réservation annulée',
  PAIEMENT_CONFIRME: 'Paiement confirmé',
}

function notifIcon(type) {
  return icons[String(type || '')] || '📬'
}

function notifTitle(type) {
  return titles[String(type || '')] || 'Notification'
}

function timeAgo(value) {
  if (!value) return 'à l\'instant'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'à l\'instant'

  const sec = Math.floor((Date.now() - date.getTime()) / 1000)
  if (sec < 60) return 'à l\'instant'
  const min = Math.floor(sec / 60)
  if (min < 60) return `il y a ${min} min`
  const hour = Math.floor(min / 60)
  if (hour < 24) return `il y a ${hour} h`
  const day = Math.floor(hour / 24)
  return `il y a ${day} j`
}

onMounted(() => {
  notifStore.fetchNotifs()
  notifStore.startPolling(30000)
})

onBeforeUnmount(() => {
  notifStore.stopPolling()
})
</script>

<style scoped>
.dashboard-notif-btn {
  position: relative;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid var(--border);
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.06);
}

.dashboard-notif-count {
  position: absolute;
  top: -6px;
  right: -6px;
  min-width: 18px;
  height: 18px;
  border-radius: 999px;
  padding: 0 5px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  background: var(--danger);
}

.dashboard-notif-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 10px);
  width: 320px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: var(--bg-secondary);
  box-shadow: 0 20px 42px rgba(0, 0, 0, 0.46);
  z-index: 80;
  overflow: hidden;
}

.dashboard-notif-head {
  padding: 12px 14px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: var(--text-primary);
}

.dashboard-notif-clear {
  color: var(--gold-light);
  font-size: 12px;
  font-weight: 700;
}

.dashboard-notif-list {
  max-height: 320px;
  overflow-y: auto;
}

.dashboard-notif-empty {
  padding: 22px;
  text-align: center;
  color: var(--text-secondary);
}

.dashboard-notif-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
}

.dashboard-notif-item:hover {
  background: rgba(255, 255, 255, 0.04);
}

.dashboard-notif-item-unread {
  background: rgba(26, 86, 219, 0.13);
}

.dashboard-notif-title {
  font-size: 13px;
  color: var(--text-primary);
  margin-bottom: 3px;
}

.dashboard-notif-message,
.dashboard-notif-date {
  font-size: 12px;
  color: var(--text-secondary);
}

.dashboard-notif-dot {
  width: 7px;
  height: 7px;
  margin-top: 4px;
  border-radius: 999px;
  background: var(--gold);
}

.dashboard-notif-shake {
  animation: bellShake 0.8s ease;
}

.dashboard-notif-footer-link {
  display: block;
  text-align: center;
  padding: 10px 12px;
  border-top: 1px solid var(--border);
  color: var(--gold-light);
  font-size: 12px;
  font-weight: 700;
}

@keyframes bellShake {
  0%, 100% { transform: rotate(0); }
  20% { transform: rotate(-12deg); }
  40% { transform: rotate(10deg); }
  60% { transform: rotate(-8deg); }
  80% { transform: rotate(6deg); }
}
</style>
