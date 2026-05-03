<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { productService } from '@/services/product.service'
import type { Product, Pagination } from '@/types'
import { formatMoney } from '@/utils/money'
import { formatDateThai } from '@/utils/date'
import { useAlert } from '@/composables/useAlert'
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import MoneyInput from '@/components/ui/MoneyInput.vue'

const alert = useAlert()

const products = ref<Product[]>([])
const pagination = ref<Pagination>({ page: 1, per_page: 10, total: 0, total_pages: 1 })
const loading = ref(false)
const searchQuery = ref('')

// Modal state
const showModal = ref(false)
const editing = ref<Product | null>(null)
const submitting = ref(false)
const form = ref({ name: '', price: 0 })
const selectedFile = ref<File | null>(null)
const imagePreview = ref('')

// Debounce timer
let searchTimer: ReturnType<typeof setTimeout> | null = null

async function fetchProducts() {
  loading.value = true
  try {
    const res = await productService.getAll(pagination.value.page, pagination.value.per_page, searchQuery.value)
    products.value = res.data
    if (res.pagination) pagination.value = res.pagination
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    pagination.value.page = 1
    fetchProducts()
  }, 300)
}

function openAdd() {
  editing.value = null
  form.value = { name: '', price: 0 }
  selectedFile.value = null
  imagePreview.value = ''
  showModal.value = true
}

function openEdit(product: Product) {
  editing.value = product
  form.value = { name: product.name, price: product.price }
  selectedFile.value = null
  imagePreview.value = product.image || ''
  showModal.value = true
}

function onImageChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) {
    selectedFile.value = file
    // Create object URL for immediate preview
    if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
      URL.revokeObjectURL(imagePreview.value)
    }
    imagePreview.value = URL.createObjectURL(file)
  }
}

async function handleSubmit() {
  if (!form.value.name.trim()) {
    alert.error('กรุณากรอกชื่อสินค้า')
    return
  }
  if (!editing.value && !selectedFile.value) {
    alert.error('กรุณาเลือกรูปภาพสินค้า')
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('name', form.value.name.trim())
    formData.append('price', String(form.value.price))
    if (selectedFile.value) formData.append('image', selectedFile.value)

    if (editing.value) {
      await productService.update(editing.value.id, formData)
      alert.success('แก้ไขสินค้าสำเร็จ')
    } else {
      await productService.create(formData)
      alert.success('เพิ่มสินค้าสำเร็จ')
    }
    showModal.value = false
    fetchProducts()
  } catch (err: unknown) {
    const message =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'เกิดข้อผิดพลาด'
    alert.error(message)
  } finally {
    submitting.value = false
  }
}

function handleDelete(product: Product) {
  alert.confirm(
    `ต้องการลบสินค้า "${product.name}" หรือไม่?`,
    async () => {
      try {
        await productService.delete(product.id)
        alert.success('ลบสินค้าสำเร็จ')
        fetchProducts()
      } catch (err: unknown) {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
          'เกิดข้อผิดพลาด'
        alert.error(message)
      }
    },
    'ลบสินค้า'
  )
}

function onPageChange(page: number) {
  pagination.value.page = page
  fetchProducts()
}

onMounted(fetchProducts)
</script>

<template>
  <div class="p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h2 class="text-lg font-semibold text-(--color-text)">จัดการสินค้า</h2>
        <p class="text-sm text-(--color-text-muted) mt-0.5">ทั้งหมด {{ pagination.total }} รายการ</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-(--color-text-muted)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="searchQuery"
            @input="onSearchInput"
            type="text"
            placeholder="ค้นหาสินค้า..."
            class="pl-9 pr-4 py-2 w-48 border border-(--color-border) rounded-(--radius-md) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
          />
        </div>
        <AppButton @click="openAdd" variant="primary" size="sm">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          เพิ่มสินค้า
        </AppButton>
      </div>
    </div>

    <!-- Products table -->
    <AppTable>
      <thead>
        <tr class="bg-(--color-bg)">
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider w-16">รูป</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">ชื่อสินค้า</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">ราคา</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider hidden sm:table-cell">วันที่เพิ่ม</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="5" class="px-4 py-10 text-center text-(--color-text-muted) text-sm">กำลังโหลด...</td>
        </tr>
        <tr v-else-if="products.length === 0">
          <td colspan="5" class="px-4 py-10 text-center text-(--color-text-muted) text-sm">ไม่พบสินค้า</td>
        </tr>
        <tr
          v-for="product in products"
          :key="product.id"
          class="border-t border-(--color-border) hover:bg-(--color-bg) transition-colors"
        >
          <!-- Product image thumbnail -->
          <td class="px-4 py-3">
            <div class="w-12 h-12 rounded-(--radius-md) overflow-hidden bg-(--color-bg) border border-(--color-border) flex items-center justify-center flex-shrink-0">
              <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                class="w-full h-full object-cover"
              />
              <svg v-else class="w-6 h-6 text-(--color-border)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </td>
          <td class="px-4 py-3 font-medium text-(--color-text)">{{ product.name }}</td>
          <td class="px-4 py-3 font-semibold text-(--color-primary)">฿{{ formatMoney(product.price) }}</td>
          <td class="px-4 py-3 text-sm text-(--color-text-muted) hidden sm:table-cell">{{ formatDateThai(product.created_at) }}</td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1.5">
              <button
                @click="openEdit(product)"
                class="p-1.5 text-(--color-text-muted) hover:text-(--color-primary) hover:bg-(--color-primary-light) rounded-(--radius-sm) transition-colors"
                title="แก้ไข"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="handleDelete(product)"
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
      :title="editing ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า'"
      size="md"
    >
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            ชื่อสินค้า <span class="text-(--color-error)">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="กรอกชื่อสินค้า"
            class="w-full px-3 py-2 border border-(--color-border) rounded-(--radius-md) bg-red-50 text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
          />
        </div>

        <!-- Price -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            ราคา <span class="text-(--color-error)">*</span>
          </label>
          <MoneyInput v-model="form.price" required placeholder="0.00" />
        </div>

        <!-- Image upload -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            รูปภาพ
            <span class="text-(--color-error)" v-if="!editing">*</span>
            <span class="text-(--color-text-muted) text-xs ml-1" v-else>(ไม่บังคับ - เว้นว่างถ้าไม่เปลี่ยน)</span>
          </label>
          <!-- Image preview -->
          <div v-if="imagePreview" class="mb-2">
            <img
              :src="imagePreview"
              alt="preview"
              class="w-24 h-24 object-cover rounded-(--radius-md) border border-(--color-border)"
            />
          </div>
          <input
            type="file"
            accept="image/*"
            @change="onImageChange"
            :class="[
              'w-full text-sm text-(--color-text-muted) cursor-pointer',
              'file:mr-3 file:py-1.5 file:px-3 file:cursor-pointer',
              'file:rounded-(--radius-sm) file:border-0 file:text-xs file:font-medium',
              'file:bg-(--color-primary-light) file:text-(--color-primary)',
              'hover:file:bg-(--color-primary) hover:file:text-white file:transition-colors',
              !editing ? 'p-2 bg-red-50 border border-dashed border-red-200 rounded-(--radius-md)' : ''
            ]"
          />
        </div>
      </form>

      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">ยกเลิก</AppButton>
        <AppButton variant="primary" :loading="submitting" @click="handleSubmit">
          {{ editing ? 'บันทึก' : 'เพิ่มสินค้า' }}
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>
