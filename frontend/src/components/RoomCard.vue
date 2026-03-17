<template>
  <article class="card">
    <div>
      <h3>{{ room.name }}</h3>
      <p class="muted">{{ room.type }} • {{ room.capacity }} {{ $t('rooms.capacity') }}</p>
      <p class="description">{{ room.description }}</p>
    </div>
    <div class="price">
      <span>{{ room.price_per_night }}€</span>
      <small>{{ $t('rooms.pricePerNight') }}</small>
    </div>
  </article>
</template>

<script setup>
defineProps({
  room: {
    type: Object,
    required: true,
  },
})
</script>

<style scoped>
.card {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.2rem;
  border-radius: 1rem;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}

.muted {
  color: #6b7280;
  margin: 0.25rem 0;
}

.description {
  margin: 0.5rem 0 0;
  color: #374151;
}

.price {
  text-align: right;
  font-weight: 700;
  color: #1f2937;
}

.price small {
  display: block;
  font-weight: 500;
  color: #9ca3af;
}
</style>
