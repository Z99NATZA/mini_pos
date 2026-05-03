import api from './api'
import type { User, ApiResponse } from '@/types'

export const userService = {
  async getAll(page = 1, perPage = 10): Promise<ApiResponse<User[]>> {
    const { data } = await api.get('/users', { params: { page, per_page: perPage } })
    return data
  },

  async create(formData: FormData): Promise<ApiResponse<User>> {
    const { data } = await api.post('/users', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return data
  },

  async update(id: number, formData: FormData): Promise<ApiResponse<User>> {
    const { data } = await api.post(`/users/${id}?_method=PUT`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return data
  },

  async delete(id: number): Promise<ApiResponse<null>> {
    const { data } = await api.delete(`/users/${id}`)
    return data
  }
}
