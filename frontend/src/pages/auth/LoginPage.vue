<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth.store";
import { useAlert } from "@/composables/useAlert";
import AppButton from "@/components/ui/AppButton.vue";

const router = useRouter();
const authStore = useAuthStore();
const alert = useAlert();

const username = ref("");
const password = ref("");
const showPassword = ref(false);
const loading = ref(false);

async function handleLogin() {
  if (!username.value.trim() || !password.value) {
    alert.error("กรุณากรอกชื่อผู้ใช้และรหัสผ่าน");
    return;
  }

  loading.value = true;
  try {
    await authStore.login(username.value.trim(), password.value);
    router.push("/dashboard");
  } catch (err: unknown) {
    const message =
      (err as { response?: { data?: { message?: string } } })?.response?.data
        ?.message || "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    alert.error(message);
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="flex w-full min-h-screen">
    <!-- Left panel — branding -->
    <div
      class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-[#1E3A5F] via-[#2D5A8E] to-[#4F75B8] flex-col items-center justify-center p-12 text-white"
    >
      <!-- Decorative circles -->
      <div
        class="absolute -top-24 -left-24 w-96 h-96 bg-white/5 rounded-full"
      />
      <div
        class="absolute -bottom-32 -right-16 w-[28rem] h-[28rem] bg-white/5 rounded-full"
      />
      <div
        class="absolute top-1/2 left-1/4 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2"
      />

      <!-- Content -->
      <div
        class="relative z-10 flex flex-col items-center text-center gap-6 max-w-sm"
      >
        <!-- Logo icon -->
        <div
          class="w-20 h-20 bg-white/15 backdrop-blur rounded-3xl flex items-center justify-center shadow-2xl border border-white/20"
        >
          <svg
            class="w-10 h-10 text-white"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
            />
          </svg>
        </div>

        <div>
          <h1 class="text-4xl font-bold tracking-tight">Mini POS</h1>
          <p class="mt-3 text-base text-white/75 leading-relaxed">
            ระบบจัดการร้านค้าแบบครบวงจร<br />ใช้งานง่าย รวดเร็ว ทุกที่ทุกเวลา
          </p>
        </div>

        <!-- Feature list -->
        <ul class="mt-2 space-y-2.5 text-sm text-white/80 self-start">
          <li class="flex items-center gap-2.5">
            <span
              class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0"
            >
              <svg
                class="w-3 h-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </span>
            จัดการสินค้า ไซต์ และท็อปปิ้ง
          </li>
          <li class="flex items-center gap-2.5">
            <span
              class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0"
            >
              <svg
                class="w-3 h-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </span>
            ระบบ POS ขายหน้าร้านแบบเรียลไทม์
          </li>
          <li class="flex items-center gap-2.5">
            <span
              class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0"
            >
              <svg
                class="w-3 h-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </span>
            Dashboard สรุปยอดขายประจำวัน
          </li>
        </ul>
      </div>
    </div>

    <!-- Right panel — login form -->
    <div
      class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10 bg-(--color-bg)"
    >
      <!-- Mobile logo -->
      <div class="lg:hidden flex flex-col items-center mb-8">
        <div
          class="w-14 h-14 bg-(--color-primary) rounded-2xl flex items-center justify-center shadow-md mb-3"
        >
          <svg
            class="w-7 h-7 text-white"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
            />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-(--color-text)">Mini POS</h1>
      </div>

      <!-- Form card -->
      <div class="w-full max-w-md">
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-(--color-text)">
            ยินดีต้อนรับ 👋
          </h2>
          <p class="text-(--color-text-muted) text-sm mt-1">
            ลงชื่อเข้าใช้งานระบบ Mini POS
          </p>
        </div>

        <div
          class="bg-(--color-surface) rounded-2xl shadow-sm border border-(--color-border) p-7"
        >
          <form @submit.prevent="handleLogin" class="space-y-5">
            <!-- Username -->
            <div>
              <label
                class="block text-sm font-medium text-(--color-text) mb-1.5"
              >
                ชื่อผู้ใช้
              </label>
              <div class="relative">
                <div
                  class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
                  <svg
                    class="w-4 h-4 text-(--color-text-muted)"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                </div>
                <input
                  v-model="username"
                  type="text"
                  required
                  placeholder="กรอกชื่อผู้ใช้"
                  autocomplete="username"
                  class="w-full pl-9 pr-3 py-2.5 border border-(--color-border) rounded-(--radius-md) bg-(--color-bg) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors placeholder:text-(--color-text-muted)"
                />
              </div>
            </div>

            <!-- Password -->
            <div>
              <label
                class="block text-sm font-medium text-(--color-text) mb-1.5"
              >
                รหัสผ่าน
              </label>
              <div class="relative">
                <div
                  class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
                  <svg
                    class="w-4 h-4 text-(--color-text-muted)"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                    />
                  </svg>
                </div>
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="กรอกรหัสผ่าน"
                  autocomplete="current-password"
                  class="w-full pl-9 pr-10 py-2.5 border border-(--color-border) rounded-(--radius-md) bg-(--color-bg) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors placeholder:text-(--color-text-muted)"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-(--color-text-muted) hover:text-(--color-text) transition-colors"
                  :aria-label="showPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'"
                >
                  <svg
                    v-if="showPassword"
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"
                    />
                  </svg>
                  <svg
                    v-else
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <AppButton
              type="submit"
              variant="primary"
              size="lg"
              :loading="loading"
              class="w-full mt-1"
            >
              เข้าสู่ระบบ
            </AppButton>
          </form>
        </div>

        <p class="text-center text-xs text-(--color-text-muted) mt-6">
          Mini POS &copy; {{ new Date().getFullYear() }}
        </p>
      </div>
    </div>
  </div>
</template>
