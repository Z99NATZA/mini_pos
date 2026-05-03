import api from './api'
import type { Size, ApiResponse } from '@/types'

export const sizeService = {
  async getAll(page = 1, perPage = 10): Promise<ApiResponse<Size[]>> {
    const { data } = await api.get('/sizes', { params: { page, per_page: perPage } })
    return data
  },

  async create(body: { name: string; price: number }): Promise<ApiResponse<Size>> {
    const { data } = await api.post('/sizes', body)
    return data
  },

  async update(id: number, body: { name: string; price: number }): Promise<ApiResponse<Size>> {
    const { data } = await api.put(`/sizes/${id}`, body)
    return data
  },

  async delete(id: number): Promise<ApiResponse<null>> {
    const { data } = await api.delete(`/sizes/${id}`)
    return data
  }
}
