import { defineStore } from 'pinia'
import { api } from '../services/api'

export const useBookingStore = defineStore('bookings', {
  state: () => ({
    bookings: [],
    loading: false,
  }),
  actions: {
    async fetchBookings(userId) {
      this.loading = true
      try {
        const response = await api.get('/bookings', { params: { user_id: userId } })
        this.bookings = response.data?.data ?? []
      } catch (error) {
        this.bookings = []
      } finally {
        this.loading = false
      }
    },
    async createBooking(payload) {
      this.loading = true
      try {
        const response = await api.post('/bookings', payload)
        if (response.data?.data) {
          this.bookings.unshift(response.data.data)
        }
        return { success: true }
      } catch (error) {
        return { success: false }
      } finally {
        this.loading = false
      }
    },
    async cancelBooking(bookingId) {
      try {
        await api.patch(`/bookings/${bookingId}/cancel`)
        this.bookings = this.bookings.map((booking) =>
          booking._id === bookingId || booking.id === bookingId
            ? { ...booking, status: 'cancelled' }
            : booking,
        )
      } catch (error) {
        // silent fallback
      }
    },
    async updateBooking(bookingId, payload) {
      this.loading = true
      try {
        const response = await api.patch(`/bookings/${bookingId}`, payload)
        if (response.data?.data) {
          this.bookings = this.bookings.map((booking) =>
            booking._id === bookingId || booking.id === bookingId ? response.data.data : booking,
          )
        }
        return { success: true }
      } catch (error) {
        return { success: false }
      } finally {
        this.loading = false
      }
    },
  },
})
