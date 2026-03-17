<template>
  <section class="page">
    <header>
      <h2>{{ $t('bookingHistory.title') }}</h2>
      <p class="subtitle">{{ $t('bookingHistory.subtitle') }}</p>
    </header>
    <div class="list">
      <article v-for="booking in bookings" :key="booking._id || booking.id" class="booking-card">
        <div>
          <h3>{{ booking.room_id?.name || booking.room_id || 'Chambre' }}</h3>
          <p>{{ booking.check_in }} → {{ booking.check_out }}</p>
          <div v-if="editingId === (booking._id || booking.id)" class="edit-form">
            <label>
              {{ $t('booking.checkIn') }}
              <input v-model="editForm.check_in" type="date" />
            </label>
            <label>
              {{ $t('booking.checkOut') }}
              <input v-model="editForm.check_out" type="date" />
            </label>
            <label>
              {{ $t('booking.guests') }}
              <input v-model.number="editForm.guests" type="number" min="1" />
            </label>
          </div>
        </div>
        <div class="status">
          <span>{{ $t('bookingHistory.status') }}: {{ booking.status }}</span>
          <div class="actions">
            <button
              v-if="editingId !== (booking._id || booking.id)"
              type="button"
              class="secondary"
              @click="startEdit(booking)"
            >
              {{ $t('bookingHistory.modify') }}
            </button>
            <button
              v-else
              type="button"
              class="secondary"
              @click="saveEdit(booking)"
            >
              {{ $t('bookingHistory.save') }}
            </button>
            <button type="button" @click="cancel(booking._id || booking.id)">
              {{ $t('bookingHistory.cancel') }}
            </button>
          </div>
        </div>
      </article>
      <p v-if="!bookings.length" class="empty">{{ $t('bookingHistory.empty') }}</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, computed, ref } from 'vue'
import { useBookingStore } from '../stores/bookingStore'
import { useUserStore } from '../stores/userStore'

const bookingStore = useBookingStore()
const userStore = useUserStore()
const bookings = computed(() => bookingStore.bookings)
const editingId = ref(null)
const editForm = ref({
  check_in: '',
  check_out: '',
  guests: 1,
})

const cancel = (bookingId) => {
  bookingStore.cancelBooking(bookingId)
}

const toDateInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toISOString().slice(0, 10)
}

const startEdit = (booking) => {
  editingId.value = booking._id || booking.id
  editForm.value = {
    check_in: toDateInput(booking.check_in),
    check_out: toDateInput(booking.check_out),
    guests: booking.guests || 1,
  }
}

const saveEdit = async (booking) => {
  const bookingId = booking._id || booking.id
  await bookingStore.updateBooking(bookingId, editForm.value)
  editingId.value = null
}

onMounted(() => {
  bookingStore.fetchBookings(userStore.user.id)
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

.list {
  display: grid;
  gap: 1rem;
}

.booking-card {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.2rem;
  border-radius: 1rem;
  background: #fff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.status {
  display: grid;
  gap: 0.5rem;
  text-align: right;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: flex-end;
}

.status button {
  border: none;
  background: #ef4444;
  color: #fff;
  padding: 0.4rem 0.8rem;
  border-radius: 0.6rem;
  cursor: pointer;
}

.status .secondary {
  background: #1d4ed8;
}

.edit-form {
  display: grid;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.edit-form label {
  display: grid;
  gap: 0.3rem;
  font-size: 0.9rem;
}

.edit-form input {
  padding: 0.4rem 0.6rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
}

.empty {
  color: #9ca3af;
}
</style>
