<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { dashboardService } from '@/services/dashboard.service'
import type { DashboardStats } from '@/types'
import { formatMoney } from '@/utils/money'
import { formatDateThai } from '@/utils/date'

const stats = ref<DashboardStats | null>(null)
const loading = ref(true)

// Last 7 days of sales in reverse chronological order
const recentSales = computed(() => {
  if (!stats.value?.monthly_sales) return []
  return [...stats.value.monthly_sales].slice(-7).reverse()
})

const statCards = computed(() => {
  if (!stats.value) return []
  return [
    {
      label: 'ยอดขายวันนี้',
      value: `฿${formatMoney(stats.value.today_sales)}`,
      iconBg: 'bg-(--color-primary-light)',
      iconColor: 'text-(--color-primary)',
      icon: 'money',
    },
    {
      label: 'ออเดอร์วันนี้',
      value: String(stats.value.today_orders),
      iconBg: 'bg-(--color-success-light)',
      iconColor: 'text-(--color-success)',
      icon: 'orders',
    },
    {
      label: 'ออเดอร์ทั้งหมด',
      value: String(stats.value.total_orders),
      iconBg: 'bg-(--color-warning-light)',
      iconColor: 'text-(--color-warning)',
      icon: 'cart',
    },
    {
      label: 'สินค้าทั้งหมด',
      value: String(stats.value.total_products),
      iconBg: 'bg-(--color-primary-light)',
      iconColor: 'text-(--color-primary)',
      icon: 'box',
    },
  ]
})

onMounted(async () => {
  try {
    stats.value = await dashboardService.getStats()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="flex items-center gap-3 text-(--color-text-muted)">
        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span class="text-sm">กำลังโหลดข้อมูล...</span>
      </div>
    </div>

    <template v-else-if="stats">
      <!-- Stat cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div
          v-for="card in statCards"
          :key="card.label"
          class="bg-(--color-surface) rounded-(--radius-lg) border border-(--color-border) p-5 hover:shadow-sm transition-shadow"
        >
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm text-(--color-text-muted) mb-2">{{ card.label }}</p>
              <p class="text-2xl font-bold text-(--color-text)">{{ card.value }}</p>
            </div>
            <div :class="['w-11 h-11 rounded-(--radius-md) flex items-center justify-center flex-shrink-0', card.iconBg]">
              <!-- Money icon -->
              <svg v-if="card.icon === 'money'" :class="['w-5 h-5', card.iconColor]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <!-- Orders icon -->
              <svg v-else-if="card.icon === 'orders'" :class="['w-5 h-5', card.iconColor]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              <!-- Cart icon -->
              <svg v-else-if="card.icon === 'cart'" :class="['w-5 h-5', card.iconColor]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <!-- Box icon -->
              <svg v-else :class="['w-5 h-5', card.iconColor]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent sales table -->
      <div class="bg-(--color-surface) rounded-(--radius-lg) border border-(--color-border)">
        <div class="px-6 py-4 border-b border-(--color-border)">
          <h2 class="font-semibold text-(--color-text)">ยอดขาย 7 วันล่าสุด</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-(--color-bg)">
                <th class="px-6 py-3 text-left font-medium text-(--color-text-muted) text-xs uppercase tracking-wider">วันที่</th>
                <th class="px-6 py-3 text-right font-medium text-(--color-text-muted) text-xs uppercase tracking-wider">ยอดขาย</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="sale in recentSales"
                :key="sale.date"
                class="border-t border-(--color-border) hover:bg-(--color-bg) transition-colors"
              >
                <td class="px-6 py-3.5 text-(--color-text)">{{ formatDateThai(sale.date) }}</td>
                <td class="px-6 py-3.5 text-right font-semibold text-(--color-primary)">
                  ฿{{ formatMoney(sale.total) }}
                </td>
              </tr>
              <tr v-if="recentSales.length === 0">
                <td colspan="2" class="px-6 py-10 text-center text-(--color-text-muted)">ไม่มีข้อมูล</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
