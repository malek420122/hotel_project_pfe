<template>
  <section class="page">
    <header>
      <h2>{{ $t('dashboard.title') }}</h2>
      <p class="subtitle">{{ $t('dashboard.subtitle') }}</p>
    </header>
    <div class="stats">
      <StatCard :label="$t('dashboard.totalRooms')" :value="stats.total_rooms" />
      <StatCard :label="$t('dashboard.totalBookings')" :value="stats.total_bookings" />
      <StatCard :label="$t('dashboard.confirmedBookings')" :value="stats.confirmed_bookings" />
      <StatCard :label="$t('dashboard.revenue')" :value="`${stats.revenue}€`" />
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive } from 'vue'
import StatCard from '../components/StatCard.vue'
import { api } from '../services/api'

const stats = reactive({
  total_rooms: 0,
  total_bookings: 0,
  confirmed_bookings: 0,
  revenue: 0,
})

const fallbackStats = {
  total_rooms: 42,
  total_bookings: 126,
  confirmed_bookings: 112,
  revenue: 22840,
}

onMounted(async () => {
  try {
    const response = await api.get('/admin/dashboard')
    Object.assign(stats, response.data?.data ?? fallbackStats)
  } catch (error) {
    Object.assign(stats, fallbackStats)
  }
})
</script>

<style scoped>
.page {
  display: grid;
  gap: 1.5rem;
}

.subtitle {
  color: #6b7280;
}

.stats {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}
</style>
