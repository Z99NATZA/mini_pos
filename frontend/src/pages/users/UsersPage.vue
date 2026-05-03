<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { userService } from '@/services/user.service'
import { useAuthStore } from '@/stores/auth.store'
import type { User, Pagination } from '@/types'
import { formatDateThai } from '@/utils/date'
import { useAlert } from '@/composables/useAlert'
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppPagination from '@/components/ui/AppPagination.vue'

const alert = useAlert()
const authStore = useAuthStore()

const users = ref<User[]>([])
const pagination = ref<Pagination>({ page: 1, per_page: 10, total: 0, total_pages: 1 })
const loading = ref(false)

// Modal state
const showModal = ref(false)
const editing = ref<User | null>(null)
const submitting = ref(false)
const form = ref({
  username: '',
  name: '',
  password: '',
  role: 'staff' as 'admin' | 'staff',
})
const selectedFile = ref<File | null>(null)
const imagePreview = ref('')
const showPassword = ref(false)

async function fetchUsers() {
  loading.value = true
  try {
    const res = await userService.getAll(pagination.value.page, pagination.value.per_page)
    users.value = res.data
    if (res.pagination) pagination.value = res.pagination
  } finally {
    loading.value = false
  }
}

function openAdd() {
  editing.value = null
  form.value = { username: '', name: '', password: '', role: 'staff' }
  selectedFile.value = null
  imagePreview.value = ''
  showPassword.value = false
  showModal.value = true
}

function openEdit(user: User) {
  editing.value = user
  form.value = { username: user.username, name: user.name, password: '', role: user.role }
  selectedFile.value = null
  imagePreview.value = user.image || ''
  showPassword.value = false
  showModal.value = true
}

function onImageChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) {
    selectedFile.value = file
    if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
      URL.revokeObjectURL(imagePreview.value)
    }
    imagePreview.value = URL.createObjectURL(file)
  }
}

async function handleSubmit() {
  if (!form.value.username.trim()) {
    alert.error('กรุณากรอกชื่อผู้ใช้')
    return
  }
  if (!form.value.name.trim()) {
    alert.error('กรุณากรอกชื่อ-นามสกุล')
    return
  }
  if (!editing.value && !form.value.password) {
    alert.error('กรุณากรอกรหัสผ่าน')
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('username', form.value.username.trim())
    formData.append('name', form.value.name.trim())
    formData.append('role', form.value.role)
    if (form.value.password) formData.append('password', form.value.password)
    if (selectedFile.value) formData.append('image', selectedFile.value)

    if (editing.value) {
      await userService.update(editing.value.id, formData)
      alert.success('แก้ไขผู้ใช้สำเร็จ')
    } else {
      await userService.create(formData)
      alert.success('เพิ่มผู้ใช้สำเร็จ')
    }
    showModal.value = false
    fetchUsers()
  } catch (err: unknown) {
    const message =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'เกิดข้อผิดพลาด'
    alert.error(message)
  } finally {
    submitting.value = false
  }
}

function handleDelete(user: User) {
  // Prevent deleting own account
  if (user.id === authStore.user?.id) {
    alert.error('ไม่สามารถลบบัญชีของตัวเองได้')
    return
  }

  alert.confirm(
    `ต้องการลบผู้ใช้ "${user.name}" หรือไม่?`,
    async () => {
      try {
        await userService.delete(user.id)
        alert.success('ลบผู้ใช้สำเร็จ')
        fetchUsers()
      } catch (err: unknown) {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
          'เกิดข้อผิดพลาด'
        alert.error(message)
      }
    },
    'ลบผู้ใช้'
  )
}

function onPageChange(page: number) {
  pagination.value.page = page
  fetchUsers()
}

function roleLabel(role: string) {
  return role === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน'
}

onMounted(fetchUsers)
</script>

<template>
  <div class="p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-(--color-text)">จัดการผู้ใช้งาน</h2>
        <p class="text-sm text-(--color-text-muted) mt-0.5">ทั้งหมด {{ pagination.total }} รายการ</p>
      </div>
      <AppButton @click="openAdd" variant="primary" size="sm">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        เพิ่มผู้ใช้
      </AppButton>
    </div>

    <!-- Users table -->
    <AppTable>
      <thead>
        <tr class="bg-(--color-bg)">
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">ผู้ใช้</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider hidden sm:table-cell">ชื่อผู้ใช้</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">สิทธิ์</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-(--color-text-muted) uppercase tracking-wider hidden md:table-cell">วันที่สร้าง</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-(--color-text-muted) uppercase tracking-wider">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="5" class="px-4 py-10 text-center text-(--color-text-muted) text-sm">กำลังโหลด...</td>
        </tr>
        <tr v-else-if="users.length === 0">
          <td colspan="5" class="px-4 py-10 text-center text-(--color-text-muted) text-sm">ยังไม่มีผู้ใช้งาน</td>
        </tr>
        <tr
          v-for="user in users"
          :key="user.id"
          class="border-t border-(--color-border) hover:bg-(--color-bg) transition-colors"
          :class="user.id === authStore.user?.id ? 'bg-(--color-primary-light)/30' : ''"
        >
          <!-- User avatar + name -->
          <td class="px-4 py-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-(--color-primary-light) border border-(--color-border) overflow-hidden flex items-center justify-center flex-shrink-0">
                <img
                  v-if="user.image"
                  :src="user.image"
                  :alt="user.name"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-sm font-semibold text-(--color-primary)">
                  {{ user.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div>
                <p class="font-medium text-(--color-text) text-sm leading-tight">{{ user.name }}</p>
                <p v-if="user.id === authStore.user?.id" class="text-xs text-(--color-primary)">คุณ</p>
              </div>
            </div>
          </td>
          <!-- Username -->
          <td class="px-4 py-3 text-sm text-(--color-text-muted) hidden sm:table-cell">
            @{{ user.username }}
          </td>
          <!-- Role badge -->
          <td class="px-4 py-3">
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                user.role === 'admin'
                  ? 'bg-(--color-primary-light) text-(--color-primary)'
                  : 'bg-(--color-bg) text-(--color-text-muted) border border-(--color-border)'
              ]"
            >
              {{ roleLabel(user.role) }}
            </span>
          </td>
          <!-- Created at -->
          <td class="px-4 py-3 text-sm text-(--color-text-muted) hidden md:table-cell">
            {{ formatDateThai(user.created_at) }}
          </td>
          <!-- Actions -->
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1.5">
              <button
                @click="openEdit(user)"
                class="p-1.5 text-(--color-text-muted) hover:text-(--color-primary) hover:bg-(--color-primary-light) rounded-(--radius-sm) transition-colors"
                title="แก้ไข"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="handleDelete(user)"
                :disabled="user.id === authStore.user?.id"
                :title="user.id === authStore.user?.id ? 'ไม่สามารถลบบัญชีตัวเองได้' : 'ลบ'"
                class="p-1.5 text-(--color-text-muted) hover:text-(--color-error) hover:bg-(--color-error-light) rounded-(--radius-sm) transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-(--color-text-muted)"
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

    <!-- Add/Edit user modal -->
    <AppModal
      v-model="showModal"
      :title="editing ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้'"
      size="md"
    >
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Username + Name (2-col grid) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
              ชื่อผู้ใช้ <span class="text-(--color-error)">*</span>
            </label>
            <input
              v-model="form.username"
              type="text"
              required
              placeholder="username"
              autocomplete="off"
              class="w-full px-3 py-2 border border-(--color-border) rounded-(--radius-md) hidden-bg-red-50 text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-(--color-text) mb-1.5">
              ชื่อ-นามสกุล <span class="text-(--color-error)">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="ชื่อ นามสกุล"
              class="w-full px-3 py-2 border border-(--color-border) rounded-(--radius-md) hidden-bg-red-50 text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
            />
          </div>
        </div>

        <!-- Password -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            รหัสผ่าน
            <span class="text-(--color-error)" v-if="!editing">*</span>
            <span class="text-(--color-text-muted) text-xs ml-1" v-else>(เว้นว่างถ้าไม่เปลี่ยน)</span>
          </label>
          <div class="relative">
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              :required="!editing"
              placeholder="รหัสผ่าน"
              autocomplete="new-password"
              :class="[
                'w-full px-3 py-2 pr-10 border border-(--color-border) rounded-(--radius-md) text-(--color-text) text-sm',
                'focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors',
                !editing ? 'hidden-bg-red-50' : 'bg-(--color-surface)'
              ]"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-(--color-text-muted) hover:text-(--color-text)"
            >
              <svg v-if="showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Role selector -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            สิทธิ์ <span class="text-(--color-error)">*</span>
          </label>
          <select
            v-model="form.role"
            class="w-full px-3 py-2 border border-(--color-border) rounded-(--radius-md) hidden-bg-red-50 text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
          >
            <option value="admin">ผู้ดูแลระบบ (Admin)</option>
            <option value="staff">พนักงาน (Staff)</option>
          </select>
        </div>

        <!-- Image upload (optional) -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            รูปโปรไฟล์ <span class="text-(--color-text-muted) text-xs">(ไม่บังคับ)</span>
          </label>
          <!-- Preview -->
          <div v-if="imagePreview" class="mb-2">
            <img
              :src="imagePreview"
              alt="preview"
              class="w-16 h-16 object-cover rounded-full border-2 border-(--color-border)"
            />
          </div>
          <input
            type="file"
            accept="image/*"
            @change="onImageChange"
            class="w-full text-sm text-(--color-text-muted) cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:cursor-pointer file:rounded-(--radius-sm) file:border-0 file:text-xs file:font-medium file:bg-(--color-primary-light) file:text-(--color-primary) hover:file:bg-(--color-primary) hover:file:text-white file:transition-colors"
          />
        </div>
      </form>

      <template #footer>
        <AppButton variant="secondary" @click="showModal = false">ยกเลิก</AppButton>
        <AppButton variant="primary" :loading="submitting" @click="handleSubmit">
          {{ editing ? 'บันทึก' : 'เพิ่มผู้ใช้' }}
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>
