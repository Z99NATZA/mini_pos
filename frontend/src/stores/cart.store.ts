import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { CartItem, Product, Size, Type, Topping } from '@/types'

export const useCartStore = defineStore('cart', () => {
  const items = ref<CartItem[]>([])

  const totalAmount = computed(() =>
    items.value.reduce((sum, item) => sum + item.amount, 0)
  )

  const totalItems = computed(() => items.value.length)

  // Calculate total price for a single cart item configuration
  function calculateItemAmount(
    product: Product,
    size: Size | null,
    type: Type | null,
    toppings: Topping[],
    quantity: number
  ): number {
    const base = product.price
    const sizePrice = size?.price || 0
    const typePrice = type?.price || 0
    const toppingsPrice = toppings.reduce((s, t) => s + t.price, 0)
    return (base + sizePrice + typePrice + toppingsPrice) * quantity
  }

  function addItem(
    product: Product,
    size: Size | null,
    type: Type | null,
    toppings: Topping[],
    quantity: number
  ) {
    const amount = calculateItemAmount(product, size, type, toppings, quantity)
    const id = `${Date.now()}-${Math.random()}`
    items.value.push({ id, product, size, type, toppings, quantity, amount })
  }

  function removeItem(id: string) {
    items.value = items.value.filter(item => item.id !== id)
  }

  function clearCart() {
    items.value = []
  }

  return { items, totalAmount, totalItems, addItem, removeItem, clearCart, calculateItemAmount }
})
