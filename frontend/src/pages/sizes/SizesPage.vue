<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { sizeService } from '@/services/size.service'
import type { Size, Pagination } from '@/types'
import { formatMoney } from '@/utils/money'
import { useAlert } from '@/composables/useAlert'
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import MoneyInput from '@/components/ui/MoneyInput.vue'

const alert = useAlert()

const sizes = ref<Size[]>([])
const pagination = ref<Pagination>({ page: 1, per_page: 10, total: 0, total_pages: 1 })
const loading = ref(false)

// Modal state
const showModal = ref(false)
const editing = ref<Size | null>(null)
const submitting = ref(false)
const form = ref({ name: '', price: 0 })

async function fetchSizes() {
  loading.value = true
  try {
    const res = await sizeService.getAll(pagination.value.page, pagination.value.per_page)
    sizes.value = res.data
    if (res.pagination) pagination.value = res.pagination
  } finally {
    loading.value = false
  }
}

function openAdd() {
  editing.value = null
  form.value = { name: '', price: 0 }
  showModal.value = true
}

function openEdit(size: Size) {
  editing.value = size
  form.value = { name: size.name, price: size.price }
  showModal.value = true
}

async function handleSubmit() {
  if (!form.value.name.trim()) {
    alert.error('กรุณากรอกชื่อไซต์')
    return
  }

  submitting.value = true
  try {
    if (editing.value) {
      await sizeService.update(editing.value.id, { name: form.value.name.trim(), price: form.value.price })
      alert.success('แก้ไขไซต์สำเร็จ')
    } else {
      await sizeService.create({ name: form.value.name.trim(), price: form.value.price })
      alert.success('เพิ่มไซต์สำเร็จ')
    }
    showModal.value = false
    fetchSizes()
  } catch (err: unknown) {
    const message =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'เกิดข้อผิดพลาด'
    alert.error(message)
  } finally {
    submitting.value = false
  }
}

function handleDelete(size: Size) {
  alert.confirm(
    `ต้องการลบไซต์ "${size.name}" หรือไม่?`,
    async () => {
      try {
        await sizeService.delete(size.id)
        alert.success('ลบไซต์สำเร็จ')
        fetchSizes()
      } catch (err: unknown) {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
          'เกิดข้อผิดพลาด'
        alert.error(message)
      }
    },
    'ลบไซต์'
  )
}

function onPageChange(page: number) {
  pagination.value.page = page
  fetchSizes()
}

onMounted(fetchSizes)
</script>

<template>
  <div class="p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-(--color-text)">จัดการไซต์</h2>
        <p class="text-sm text-(--color-text-muted) mt-0.5">ทั้งหมด {{ pagination.total }} รายการ</p>
      </div>
      <AppButton @click="openAdd" variant="primary" size="sm">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        เพิ่มไซต์
      </AppButton>
    </div>

    <!-- Table -->
    <AppTable>
      <thead>
        <tr class="bg-(--color-bg)">
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">#</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">ชื่อไซต์</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">ราคาเพิ่ม</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="4" class="px-4 py-10 text-center text-(--color-text-muted) text-sm">กำลังโหลด...</td>
        </tr>
        <tr v-else-if="sizes.length === 0">
          <td colspan="4" class="px-4 py-10 text-center text-(--color-text-muted) text-sm">ยังไม่มีข้อมูลไซต์</td>
        </tr>
        <tr
          v-for="(size, index) in sizes"
          :key="size.id"
          class="border-t border-(--color-border) hover:bg-(--color-bg) transition-colors"
        >
          <td class="px-4 py-3 text-(--color-text-muted) text-sm">
            {{ (pagination.page - 1) * pagination.per_page + index + 1 }}
          </td>
          <td class="px-4 py-3 font-medium text-(--color-text)">{{ size.name }}</td>
          <td class="px-4 py-3 text-(--color-text)">
            <span v-if="size.price > 0" class="text-(--color-primary) font-medium">+฿{{ formatMoney(size.price) }}</span>
            <span v-else class="text-(--color-text-muted) text-sm">ฟรี</span>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1.5">
              <button
                @click="openEdit(size)"
                class="p-1.5 text-(--color-text-muted) hover:text-(--color-primary) hover:bg-(--color-primary-light) rounded-(--radius-sm) transition-colors"
                title="แก้ไข"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="handleDelete(size)"
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

    <!-- Add/Edit modal -->
    <AppModal
      v-model="showModal"
      :title="editing ? 'แก้ไขไซต์' : 'เพิ่มไซต์'"
      size="sm"
    >
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            ชื่อไซต์ <span class="text-(--color-error)">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="เช่น S, M, L, XL"
            class="w-full px-3 py-2 border border-(--color-border) rounded-(--radius-md) bg-red-50 text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            ราคาเพิ่ม <span class="text-(--color-error)">*</span>
          </label>
          <MoneyInput v-model="form.price" required placeholder="0.00" />
          <p class="text-xs text-(--color-text-muted) mt-1">ใส่ 0 หากไม่มีค่าเพิ่มเติม</p>
        </div>
      </form>

      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">ยกเลิก</AppButton>
        <AppButton variant="primary" :loading="submitting" @click="handleSubmit">
          {{ editing ? 'บันทึก' : 'เพิ่มไซต์' }}
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>
