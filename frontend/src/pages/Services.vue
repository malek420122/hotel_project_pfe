<template>
  <section class="page">
    <header>
      <h2>{{ $t('services.title') }}</h2>
      <p class="subtitle">{{ $t('services.subtitle') }}</p>
    </header>
    <div class="grid">
      <ServiceCard v-for="service in services" :key="service.id || service._id" :service="service" />
    </div>
  </section>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import ServiceCard from '../components/ServiceCard.vue'
import { useServiceStore } from '../stores/serviceStore'

const serviceStore = useServiceStore()
const services = computed(() => serviceStore.services)

onMounted(() => {
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

.grid {
  display: grid;
  gap: 1rem;
}
</style>
