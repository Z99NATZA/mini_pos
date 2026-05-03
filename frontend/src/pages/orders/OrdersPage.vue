<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { orderService } from '@/services/order.service'
import type { Order, OrderItem, Pagination } from '@/types'
import { formatMoney } from '@/utils/money'
import { formatDateTime } from '@/utils/date'
import { useAlert } from '@/composables/useAlert'
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppPagination from '@/components/ui/AppPagination.vue'

const alert = useAlert()

const orders = ref<Order[]>([])
const pagination = ref<Pagination>({ page: 1, per_page: 15, total: 0, total_pages: 1 })
const loading = ref(false)

// Receipt modal
const showReceiptModal = ref(false)
const selectedOrder = ref<(Order & { items: OrderItem[] }) | null>(null)
const loadingReceipt = ref(false)

async function fetchOrders() {
  loading.value = true
  try {
    const res = await orderService.getAll(pagination.value.page, pagination.value.per_page)
    orders.value = res.data
    if (res.pagination) pagination.value = res.pagination
  } catch {
    // Silent error
  } finally {
    loading.value = false
  }
}

async function viewReceipt(order: Order) {
  selectedOrder.value = null
  loadingReceipt.value = true
  showReceiptModal.value = true
  try {
    const res = await orderService.getById(order.id)
    selectedOrder.value = res.data
  } catch {
    alert.error('ไม่สามารถโหลดข้อมูลใบเสร็จได้')
    showReceiptModal.value = false
  } finally {
    loadingReceipt.value = false
  }
}

function handleDelete(order: Order) {
  alert.confirm(
    `ต้องการลบรายการขาย #${order.order_number} หรือไม่?`,
    async () => {
      try {
        await orderService.delete(order.id)
        alert.success('ลบรายการขายสำเร็จ')
        fetchOrders()
      } catch (err: unknown) {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
          'เกิดข้อผิดพลาด'
        alert.error(message)
      }
    },
    'ลบรายการขาย'
  )
}

function onPageChange(page: number) {
  pagination.value.page = page
  fetchOrders()
}

onMounted(fetchOrders)
</script>

<template>
  <div class="p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-(--color-text)">รายการขาย</h2>
        <p class="text-sm text-(--color-text-muted) mt-0.5">ทั้งหมด {{ pagination.total }} รายการ</p>
      </div>
    </div>

    <!-- Orders table -->
    <AppTable>
      <thead>
        <tr class="bg-(--color-bg)">
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">เลขที่</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">วันที่/เวลา</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">แคชเชียร์</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">ยอดรวม</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">รับมา</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">ทอน</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="7" class="px-4 py-10 text-center text-(--color-text-muted) text-sm">กำลังโหลด...</td>
        </tr>
        <tr v-else-if="orders.length === 0">
          <td colspan="7" class="px-4 py-10 text-center text-(--color-text-muted) text-sm">ไม่มีข้อมูล</td>
        </tr>
        <tr
          v-for="order in orders"
          :key="order.id"
          class="border-t border-(--color-border) hover:bg-(--color-bg) transition-colors"
        >
          <td class="px-4 py-3">
            <span class="font-mono text-sm font-medium text-(--color-text)">{{ order.order_number }}</span>
          </td>
          <td class="px-4 py-3 text-sm text-(--color-text-muted)">{{ formatDateTime(order.created_at) }}</td>
          <td class="px-4 py-3 text-sm text-(--color-text)">{{ order.cashier_name }}</td>
          <td class="px-4 py-3 text-right font-semibold text-(--color-text)">฿{{ formatMoney(order.total_amount) }}</td>
          <td class="px-4 py-3 text-right text-sm text-(--color-text)">฿{{ formatMoney(order.received_amount) }}</td>
          <td class="px-4 py-3 text-right text-sm text-(--color-success) font-medium">฿{{ formatMoney(order.change_amount) }}</td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1.5">
              <!-- View receipt -->
              <button
                @click="viewReceipt(order)"
                class="p-1.5 text-(--color-text-muted) hover:text-(--color-primary) hover:bg-(--color-primary-light) rounded-(--radius-sm) transition-colors"
                title="ดูใบเสร็จ"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </button>
              <!-- Delete -->
              <button
                @click="handleDelete(order)"
                class="p-1.5 text-(--color-text-muted) hover:text-(--color-error) hover:bg-(--color-error-light) rounded-(--radius-sm) transition-colors"
                title="ลบ"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </AppTable>

    <!-- Pagination -->
    <AppPagination
      v-if="pagination.total_pages > 1"
      :current-page="pagination.page"
      :total-pages="pagination.total_pages"
      :total="pagination.total"
      :per-page="pagination.per_page"
      @change="onPageChange"
    />

    <!-- Receipt Modal -->
    <AppModal v-model="showReceiptModal" title="ใบเสร็จรับเงิน" size="lg">
      <!-- Loading receipt -->
      <div v-if="loadingReceipt" class="flex items-center justify-center py-12 text-(--color-text-muted)">
        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        กำลังโหลด...
      </div>

      <div v-else-if="selectedOrder" class="space-y-5">
        <!-- Order meta info -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
          <div>
            <p class="text-(--color-text-muted) text-xs uppercase tracking-wider mb-0.5">เลขที่ใบเสร็จ</p>
            <p class="font-mono font-semibold text-(--color-text)">{{ selectedOrder.order_number }}</p>
          </div>
          <div>
            <p class="text-(--color-text-muted) text-xs uppercase tracking-wider mb-0.5">วันที่/เวลา</p>
            <p class="text-(--color-text)">{{ formatDateTime(selectedOrder.created_at) }}</p>
          </div>
          <div>
            <p class="text-(--color-text-muted) text-xs uppercase tracking-wider mb-0.5">แคชเชียร์</p>
            <p class="text-(--color-text)">{{ selectedOrder.cashier_name }}</p>
          </div>
        </div>

        <!-- Order items table -->
        <div class="border border-(--color-border) rounded-(--radius-md) overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-(--color-bg)">
              <tr>
                <th class="px-4 py-2.5 text-left font-medium text-(--color-text-muted) text-xs">สินค้า</th>
                <th class="px-4 py-2.5 text-center font-medium text-(--color-text-muted) text-xs">จำนวน</th>
                <th class="px-4 py-2.5 text-right font-medium text-(--color-text-muted) text-xs">ราคา</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in selectedOrder.items"
                :key="item.id"
                class="border-t border-(--color-border)"
              >
                <td class="px-4 py-3">
                  <p class="font-medium text-(--color-text)">{{ item.product_name }}</p>
                  <p class="text-xs text-(--color-text-muted) mt-0.5">
                    <span v-if="item.size_name && item.size_name !== '-'">{{ item.size_name }}</span>
                    <span v-if="item.size_name && item.size_name !== '-' && item.type_name && item.type_name !== '-'"> / </span>
                    <span v-if="item.type_name && item.type_name !== '-'">{{ item.type_name }}</span>
                    <span v-if="item.toppings && item.toppings.length">
                      {{ (item.size_name !== '-' || item.type_name !== '-') ? ' + ' : '' }}
                      {{ item.toppings.map(t => t.topping_name).join(', ') }}
                    </span>
                  </p>
                </td>
                <td class="px-4 py-3 text-center text-(--color-text)">{{ item.quantity }}</td>
                <td class="px-4 py-3 text-right font-medium text-(--color-text)">฿{{ formatMoney(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Payment summary -->
        <div class="bg-(--color-bg) rounded-(--radius-md) p-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-(--color-text-muted)">ยอดรวม</span>
            <span class="font-semibold text-(--color-text)">฿{{ formatMoney(selectedOrder.total_amount) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-(--color-text-muted)">รับมา</span>
            <span class="text-(--color-text)">฿{{ formatMoney(selectedOrder.received_amount) }}</span>
          </div>
          <div class="flex justify-between pt-2 border-t border-(--color-border)">
            <span class="font-semibold text-(--color-text)">เงินทอน</span>
            <span class="font-bold text-(--color-success) text-base">฿{{ formatMoney(selectedOrder.change_amount) }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <AppButton variant="secondary" @click="showReceiptModal = false">ปิด</AppButton>
      </template>
    </AppModal>
  </div>
</template>
