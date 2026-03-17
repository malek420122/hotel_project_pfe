import { defineStore } from 'pinia'
import { api } from '../services/api'

const fallbackServices = [
  {
    id: 'service-1',
    name: 'Spa & Bien-être',
    description: 'Accès au spa, sauna et massages relaxants.',
    price: 60,
    category: 'Wellness',
  },
  {
    id: 'service-2',
    name: 'Restaurant Gourmet',
    description: 'Menu dégustation servi en chambre ou au restaurant.',
    price: 45,
    category: 'Restaurant',
  },
]

export const useServiceStore = defineStore('services', {
  state: () => ({
    services: [],
    loading: false,
  }),
  actions: {
    async fetchServices() {
      this.loading = true
      try {
        const response = await api.get('/services')
        this.services = response.data?.data?.length ? response.data.data : fallbackServices
      } catch (error) {
        this.services = fallbackServices
      } finally {
        this.loading = false
      }
    },
  },
})
