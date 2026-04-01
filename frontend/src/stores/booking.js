import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import api from '../api'

export const useBookingStore = defineStore('booking', () => {
  const reservations = ref([])
  const loading = ref(false)
  const currentBooking = reactive({
    hotel: null,
    chambre: null,
    dateArrivee: '',
    dateDepart: '',
    nbVoyageurs: 1,
    demandesSpeciales: '',
    servicesChoisis: [],
    codePromo: '',
  })

  async function fetchMyReservations() {
    loading.value = true
    try {
      const { data } = await api.get('/client/reservations')
      reservations.value = data
    } finally {
      loading.value = false
    }
  }

  async function createReservation(payload) {
    loading.value = true
    try {
      const { data } = await api.post('/reservations', payload)
      reservations.value.unshift(data)
      return data
    } finally {
      loading.value = false
    }
  }

  async function cancelReservation(id) {
    await api.delete(`/reservations/${id}`)
    const res = reservations.value.find(r => r._id === id)
    if (res) res.statut = 'ANNULEE'
  }

  function updateBooking(data) {
    Object.assign(currentBooking, data)
  }

  function resetBooking() {
    Object.assign(currentBooking, {
      hotel: null, chambre: null, dateArrivee: '', dateDepart: '',
      nbVoyageurs: 1, demandesSpeciales: '', servicesChoisis: [], codePromo: ''
    })
  }

  return { reservations, loading, currentBooking, fetchMyReservations, createReservation, cancelReservation, updateBooking, resetBooking }
})
