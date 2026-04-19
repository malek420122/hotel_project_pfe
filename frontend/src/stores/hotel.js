import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useHotelStore = defineStore('hotel', () => {
  const hotels = ref([])
  const currentHotel = ref(null)
  const chambres = ref([])
  const loading = ref(false)
  const pagination = ref({ total: 0, current_page: 1, last_page: 1, per_page: 10 })

  async function fetchHotels(params = {}) {
    loading.value = true
    try {
      const query = new URLSearchParams()

      Object.entries(params).forEach(([key, value]) => {
        if (value === '' || value === null || value === undefined) return
        if (Array.isArray(value)) {
          value.forEach((item) => {
            if (item === '' || item === null || item === undefined) return
            query.append(`${key}[]`, String(item))
          })
          return
        }

        query.append(key, String(value))
      })

      const { data } = await api.get(`/hotels?${query.toString()}`)
      if (data.data) {
        hotels.value = data.data
        pagination.value = { total: data.total, current_page: data.current_page, last_page: data.last_page, per_page: data.per_page }
      } else {
        hotels.value = data
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchHotel(id) {
    loading.value = true
    try {
      const { data } = await api.get(`/hotels/${id}`)
      currentHotel.value = data
    } finally {
      loading.value = false
    }
  }

  async function fetchChambres(hotelId, dateArrivee, dateDepart) {
    const params = {}
    if (dateArrivee) params.dateArrivee = dateArrivee
    if (dateDepart) params.dateDepart = dateDepart
    const { data } = await api.get(`/hotels/${hotelId}/chambres`, { params })
    chambres.value = data
  }

  return { hotels, currentHotel, chambres, loading, pagination, fetchHotels, fetchHotel, fetchChambres }
})
