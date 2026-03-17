import { defineStore } from 'pinia'

export const useUserStore = defineStore('user', {
  state: () => ({
    user: {
      id: 'guest-user',
      name: 'Invité',
      role: 'client',
    },
  }),
  actions: {
    setUser(user) {
      this.user = user
    },
  },
})
