<template>
  <form class="booking-form" @submit.prevent="submitBooking">
    <div class="grid">
      <label>
        {{ $t('booking.checkIn') }}
        <input v-model="form.check_in" type="date" required />
      </label>
      <label>
        {{ $t('booking.checkOut') }}
        <input v-model="form.check_out" type="date" required />
      </label>
    </div>
    <div class="grid">
      <label>
        {{ $t('booking.guests') }}
        <input v-model.number="form.guests" type="number" min="1" required />
      </label>
      <label>
        {{ $t('booking.room') }}
        <select v-model="form.room_id" required>
          <option disabled value="">--</option>
          <option v-for="room in rooms" :key="room.id || room._id" :value="room.id || room._id">
            {{ room.name }}
          </option>
        </select>
      </label>
    </div>
    <label>
      {{ $t('booking.specialRequests') }}
      <textarea v-model="form.special_requests" rows="3" />
    </label>
    <div>
      <p class="section-title">{{ $t('booking.services') }}</p>
      <div class="service-list">
        <label v-for="service in services" :key="service.id || service._id" class="service-item">
          <input
            v-model="form.services"
            type="checkbox"
            :value="service"
          />
          {{ service.name }} ({{ service.price }}€)
        </label>
      </div>
    </div>
    <button type="submit" :disabled="loading">
      {{ loading ? $t('form.loading') : $t('booking.submit') }}
    </button>
  </form>
</template>

<script setup>
import { reactive } from 'vue'

const props = defineProps({
  rooms: {
    type: Array,
    required: true,
  },
  services: {
    type: Array,
    required: true,
  },
  userId: {
    type: String,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['submit'])

const form = reactive({
  check_in: '',
  check_out: '',
  guests: 1,
  room_id: '',
  special_requests: '',
  services: [],
})

const submitBooking = () => {
  emit('submit', { ...form, user_id: props.userId })
}
</script>

<style scoped>
.booking-form {
  display: grid;
  gap: 1rem;
  background: #fff;
  padding: 1.5rem;
  border-radius: 1rem;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

label {
  display: grid;
  gap: 0.4rem;
  font-weight: 600;
  color: #1f2937;
}

.section-title {
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.service-list {
  display: grid;
  gap: 0.4rem;
}

.service-item {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  font-weight: 500;
}

input,
select,
textarea {
  padding: 0.6rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.6rem;
  font: inherit;
}

button {
  padding: 0.8rem 1.2rem;
  border-radius: 0.8rem;
  border: none;
  background: #1d4ed8;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
