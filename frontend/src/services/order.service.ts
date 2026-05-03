import api from "./api";
import type { Order, OrderItem, ApiResponse } from "@/types";

export interface CreateOrderData {
  cashier_name: string;
  received_amount: number;
  items: {
    product: { name: string; price: number; qty: number };
    size: { name: string; price: number } | null;
    type: { name: string; price: number } | null;
    toppings: { name: string; price: number }[];
    amount: number;
  }[];
}

export const orderService = {
  async getAll(page = 1, perPage = 15): Promise<ApiResponse<Order[]>> {
    const { data } = await api.get("/orders", {
      params: { page, per_page: perPage },
    });
    return data;
  },

  async getById(
    id: number,
  ): Promise<ApiResponse<Order & { items: OrderItem[] }>> {
    const { data } = await api.get(`/orders/${id}`);
    return data;
  },

  async create(orderData: CreateOrderData): Promise<ApiResponse<Order>> {
    const { data } = await api.post("/orders", orderData);
    return data;
  },

  async delete(id: number): Promise<ApiResponse<null>> {
    const { data } = await api.delete(`/orders/${id}`);
    return data;
  },
};
