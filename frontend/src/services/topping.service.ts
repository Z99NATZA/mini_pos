import api from './api'
import type { Topping, ApiResponse } from '@/types'

export const toppingService = {
  async getAll(page = 1, perPage = 10): Promise<ApiResponse<Topping[]>> {
    const { data } = await api.get('/toppings', { params: { page, per_page: perPage } })
    return data
  },

  async create(body: { name: string; price: number }): Promise<ApiResponse<Topping>> {
    const { data } = await api.post('/toppings', body)
    return data
  },

  async update(id: number, body: { name: string; price: number }): Promise<ApiResponse<Topping>> {
    const { data } = await api.put(`/toppings/${id}`, body)
    return data
  },

  async delete(id: number): Promise<ApiResponse<null>> {
    const { data } = await api.delete(`/toppings/${id}`)
    return data
  }
}
