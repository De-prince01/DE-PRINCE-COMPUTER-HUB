import { defineStore } from 'pinia'
import api, { setAuthToken } from '../lib/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null,
    user: null,
  }),
  persist: true,
  actions: {
    async login(payload) {
      const { data } = await api.post('/api/auth/login', payload)
      this.token = data.token
      this.user = data.user
      setAuthToken(this.token)
      return data
    },
    async register(payload) {
      const { data } = await api.post('/api/auth/register', payload)
      this.token = data.token
      this.user = data.user
      setAuthToken(this.token)
      return data
    },
    async logout() {
      try {
        await api.post('/api/auth/logout')
      } finally {
        this.token = null
        this.user = null
        setAuthToken(null)
      }
    },
    async fetchMe() {
      if (!this.token) return null
      setAuthToken(this.token)
      const { data } = await api.get('/api/auth/me')
      this.user = data.user
      return data.user
    },
    restore() {
      setAuthToken(this.token)
    },
  },
})

