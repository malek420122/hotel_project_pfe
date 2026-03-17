<template>
  <article class="card">
    <div>
      <h3>{{ service.name }}</h3>
      <p class="muted">{{ service.category }}</p>
      <p class="description">{{ service.description }}</p>
    </div>
    <div class="price">
      <span>{{ service.price }}€</span>
    </div>
  </article>
</template>

<script setup>
defineProps({
  service: {
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
</style>
