<template>
  <article class="stat-card">
    <p class="label">{{ label }}</p>
    <p class="value">{{ value }}</p>
  </article>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
})
</script>

<style scoped>
.stat-card {
  padding: 1.2rem;
  border-radius: 1rem;
  background: linear-gradient(135deg, #f8fafc, #eef2ff);
  border: 1px solid #e5e7eb;
}

.label {
  color: #6b7280;
  margin: 0;
  font-size: 0.9rem;
}

.value {
  font-size: 1.6rem;
  font-weight: 700;
  margin: 0.4rem 0 0;
}
</style>
