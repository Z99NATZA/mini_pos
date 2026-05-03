import api from './api'
import type { User } from '@/types'

export interface LoginCredentials {
  username: string
  password: string
}

export const authService = {
  async login(credentials: LoginCredentials): Promise<{ token: string; user: User }> {
    const { data } = await api.post('/auth/login', credentials)
    return data.data
  },

  async me(): Promise<User> {
    const { data } = await api.get('/auth/me')
    return data.data
  }
}
