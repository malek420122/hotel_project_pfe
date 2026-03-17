<template>
  <section class="page">
    <header>
      <h2>{{ $t('booking.title') }}</h2>
      <p class="subtitle">{{ $t('booking.subtitle') }}</p>
    </header>
    <div class="layout">
      <BookingForm
        :rooms="rooms"
        :services="services"
        :user-id="user.id"
        :loading="loading"
        @submit="submitBooking"
      />
    </div>
  </section>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import BookingForm from '../components/BookingForm.vue'
import { useRoomStore } from '../stores/roomStore'
import { useServiceStore } from '../stores/serviceStore'
import { useBookingStore } from '../stores/bookingStore'
import { useUserStore } from '../stores/userStore'

const roomStore = useRoomStore()
const serviceStore = useServiceStore()
const bookingStore = useBookingStore()
const userStore = useUserStore()

const rooms = computed(() => roomStore.rooms)
const services = computed(() => serviceStore.services)
const loading = computed(() => bookingStore.loading)
const user = computed(() => userStore.user)

const submitBooking = async (payload) => {
  await bookingStore.createBooking(payload)
}

onMounted(() => {
  roomStore.fetchRooms()
  serviceStore.fetchServices()
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

.layout {
  display: grid;
  gap: 1.5rem;
}
</style>
