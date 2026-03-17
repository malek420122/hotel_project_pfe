<template>
  <section class="page">
    <header>
      <h2>{{ $t('rooms.title') }}</h2>
      <p class="subtitle">{{ $t('rooms.subtitle') }}</p>
    </header>
    <div class="grid">
      <RoomCard v-for="room in rooms" :key="room.id || room._id" :room="room" />
    </div>
  </section>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import RoomCard from '../components/RoomCard.vue'
import { useRoomStore } from '../stores/roomStore'

const roomStore = useRoomStore()
const rooms = computed(() => roomStore.rooms)

onMounted(() => {
  roomStore.fetchRooms()
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
