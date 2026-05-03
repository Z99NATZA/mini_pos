import api from './api'
import type { Type, ApiResponse } from '@/types'

export const typeService = {
  async getAll(page = 1, perPage = 10): Promise<ApiResponse<Type[]>> {
    const { data } = await api.get('/types', { params: { page, per_page: perPage } })
    return data
  },

  async create(body: { name: string; price: number }): Promise<ApiResponse<Type>> {
    const { data } = await api.post('/types', body)
    return data
  },

  async update(id: number, body: { name: string; price: number }): Promise<ApiResponse<Type>> {
    const { data } = await api.put(`/types/${id}`, body)
    return data
  },

  async delete(id: number): Promise<ApiResponse<null>> {
    const { data } = await api.delete(`/types/${id}`)
    return data
  }
}
