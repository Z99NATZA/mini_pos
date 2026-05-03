import api from './api'
import type { Product, ApiResponse } from '@/types'

export const productService = {
  async getAll(page = 1, perPage = 10, search = ''): Promise<ApiResponse<Product[]>> {
    const { data } = await api.get('/products', { params: { page, per_page: perPage, search } })
    return data
  },

  async create(formData: FormData): Promise<ApiResponse<Product>> {
    const { data } = await api.post('/products', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return data
  },

  async update(id: number, formData: FormData): Promise<ApiResponse<Product>> {
    const { data } = await api.post(`/products/${id}?_method=PUT`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return data
  },

  async delete(id: number): Promise<ApiResponse<null>> {
    const { data } = await api.delete(`/products/${id}`)
    return data
  }
}
