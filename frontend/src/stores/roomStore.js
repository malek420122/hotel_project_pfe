import { defineStore } from 'pinia'
import { api } from '../services/api'

const fallbackRooms = [
  {
    id: 'room-1',
    name: 'Suite Prestige',
    type: 'Suite',
    capacity: 3,
    price_per_night: 220,
    description: 'Suite lumineuse avec vue panoramique et service premium.',
  },
  {
    id: 'room-2',
    name: 'Chambre Confort',
    type: 'Double',
    capacity: 2,
    price_per_night: 140,
    description: 'Chambre élégante avec literie haut de gamme.',
  },
]

export const useRoomStore = defineStore('rooms', {
  state: () => ({
    rooms: [],
    loading: false,
  }),
  actions: {
    async fetchRooms() {
      this.loading = true
      try {
        const response = await api.get('/rooms')
        this.rooms = response.data?.data?.length ? response.data.data : fallbackRooms
      } catch (error) {
        this.rooms = fallbackRooms
      } finally {
        this.loading = false
      }
    },
  },
})
