export interface User {
  id: number
  username: string
  name: string
  role: 'admin' | 'staff'
  image: string | null
  created_at: string
}

export interface Product {
  id: number
  name: string
  price: number
  image: string | null
  created_at: string
}

export interface Size {
  id: number
  name: string
  price: number
}

export interface Type {
  id: number
  name: string
  price: number
}

export interface Topping {
  id: number
  name: string
  price: number
}

export interface Order {
  id: number
  order_number: string
  cashier_name: string
  total_amount: number
  received_amount: number
  change_amount: number
  created_at: string
  item_count?: number
}

export interface OrderItem {
  id: number
  order_id: number
  order_item_code: string
  product_name: string
  product_price: number
  size_name: string
  size_price: number
  type_name: string
  type_price: number
  quantity: number
  amount: number
  toppings?: OrderItemTopping[]
}

export interface OrderItemTopping {
  id: number
  topping_name: string
  topping_price: number
}

// Cart types for POS
export interface CartItem {
  id: string // unique id for cart item
  product: Product
  size: Size | null
  type: Type | null
  toppings: Topping[]
  quantity: number
  amount: number // calculated total for this item
}

export interface Pagination {
  page: number
  per_page: number
  total: number
  total_pages: number
}

export interface ApiResponse<T> {
  success: boolean
  message?: string
  data: T
  pagination?: Pagination
  errors?: Record<string, string>
}

export interface DashboardStats {
  today_sales: number
  today_orders: number
  total_orders: number
  total_products: number
  monthly_sales: { date: string; total: number }[]
}

export type AlertType = 'success' | 'error' | 'warning' | 'confirm'

export interface AlertOptions {
  type: AlertType
  title?: string
  message: string
  onConfirm?: () => void
}
