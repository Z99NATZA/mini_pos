<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useCartStore } from "@/stores/cart.store";
import { useAuthStore } from "@/stores/auth.store";
import { useAlert } from "@/composables/useAlert";
import { productService } from "@/services/product.service";
import { sizeService } from "@/services/size.service";
import { typeService } from "@/services/type.service";
import { toppingService } from "@/services/topping.service";
import { orderService } from "@/services/order.service";
import type { Product, Size, Type, Topping } from "@/types";
import { formatMoney } from "@/utils/money";
import AppModal from "@/components/ui/AppModal.vue";
import AppButton from "@/components/ui/AppButton.vue";
import AppPagination from "@/components/ui/AppPagination.vue";
import MoneyInput from "@/components/ui/MoneyInput.vue";

const cartStore = useCartStore();
const authStore = useAuthStore();
const alert = useAlert();

// Product list state
const products = ref<Product[]>([]);
const searchQuery = ref("");
const currentPage = ref(1);
const totalPages = ref(1);
const totalProducts = ref(0);
const perPage = 12;
const loadingProducts = ref(false);

// Option lists loaded once on mount
const allSizes = ref<Size[]>([]);
const allTypes = ref<Type[]>([]);
const allToppings = ref<Topping[]>([]);

// Product customization modal state
const showCustomizeModal = ref(false);
const selectedProduct = ref<Product | null>(null);
const selectedSize = ref<Size | null>(null);
const selectedType = ref<Type | null>(null);
const selectedToppings = ref<Topping[]>([]);
const quantity = ref(1);

// Payment modal state
const showPaymentModal = ref(false);
const receivedAmount = ref(0);
const isSubmitting = ref(false);

// Debounce timer for search
let searchTimer: ReturnType<typeof setTimeout> | null = null;

// Computed total for current customization
const itemTotal = computed(() => {
  if (!selectedProduct.value) return 0;
  const base = selectedProduct.value.price;
  const sizePrice = selectedSize.value?.price || 0;
  const typePrice = selectedType.value?.price || 0;
  const toppingsPrice = selectedToppings.value.reduce(
    (sum, t) => sum + t.price,
    0,
  );
  return (base + sizePrice + typePrice + toppingsPrice) * quantity.value;
});

// Computed change amount for payment
const changeAmount = computed(() =>
  Math.max(0, receivedAmount.value - cartStore.totalAmount),
);

const canConfirmPayment = computed(
  () =>
    receivedAmount.value >= cartStore.totalAmount && cartStore.items.length > 0,
);

async function fetchProducts() {
  loadingProducts.value = true;
  try {
    const res = await productService.getAll(
      currentPage.value,
      perPage,
      searchQuery.value,
    );
    products.value = res.data;
    totalPages.value = res.pagination?.total_pages || 1;
    totalProducts.value = res.pagination?.total || 0;
  } catch {
    // Silent - products will show empty state
  } finally {
    loadingProducts.value = false;
  }
}

async function fetchOptions() {
  try {
    // Fetch all sizes, types and toppings (large limit to get all)
    const [sizesRes, typesRes, toppingsRes] = await Promise.all([
      sizeService.getAll(1, 999),
      typeService.getAll(1, 999),
      toppingService.getAll(1, 999),
    ]);
    allSizes.value = sizesRes.data;
    allTypes.value = typesRes.data;
    allToppings.value = toppingsRes.data;
  } catch {
    // Silent - selectors will be empty
  }
}

function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentPage.value = 1;
    fetchProducts();
  }, 300);
}

function onPageChange(page: number) {
  currentPage.value = page;
  fetchProducts();
}

// Open customization modal for a product
function openCustomize(product: Product) {
  selectedProduct.value = product;
  selectedSize.value = allSizes.value[0] || null;
  selectedType.value = allTypes.value[0] || null;
  selectedToppings.value = [];
  quantity.value = 1;
  showCustomizeModal.value = true;
}

function toggleTopping(topping: Topping) {
  const idx = selectedToppings.value.findIndex((t) => t.id === topping.id);
  if (idx === -1) {
    selectedToppings.value.push(topping);
  } else {
    selectedToppings.value.splice(idx, 1);
  }
}

function isToppingSelected(topping: Topping): boolean {
  return selectedToppings.value.some((t) => t.id === topping.id);
}

function addToCart() {
  if (!selectedProduct.value) return;
  cartStore.addItem(
    selectedProduct.value,
    selectedSize.value,
    selectedType.value,
    [...selectedToppings.value],
    quantity.value,
  );
  showCustomizeModal.value = false;
}

async function confirmPayment() {
  if (!canConfirmPayment.value) return;

  isSubmitting.value = true;
  try {
    await orderService.create({
      cashier_name: authStore.user?.name ?? "",
      received_amount: receivedAmount.value,
      items: cartStore.items.map((item) => ({
        product: {
          name: item.product.name,
          price: item.product.price,
          qty: item.quantity,
        },
        size: item.size
          ? { name: item.size.name, price: item.size.price }
          : null,
        type: item.type
          ? { name: item.type.name, price: item.type.price }
          : null,
        toppings: item.toppings.map((t) => ({ name: t.name, price: t.price })),
        amount: item.amount,
      })),
    });
    cartStore.clearCart();
    showPaymentModal.value = false;
    receivedAmount.value = 0;
    alert.success("ชำระเงินสำเร็จ");
  } catch (err: unknown) {
    const message =
      (err as { response?: { data?: { message?: string } } })?.response?.data
        ?.message || "เกิดข้อผิดพลาด กรุณาลองใหม่";
    alert.error(message);
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(() => {
  fetchProducts();
  fetchOptions();
});
</script>

<template>
  <div class="flex h-[calc(100vh-4rem)] overflow-hidden">
    <!-- Left panel: Product grid -->
    <div class="flex-1 flex flex-col overflow-hidden p-4 gap-4 min-w-0">
      <!-- Search bar -->
      <div class="flex-shrink-0">
        <div class="relative">
          <svg
            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-(--color-text-muted)"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          <input
            v-model="searchQuery"
            @input="onSearchInput"
            type="text"
            placeholder="ค้นหาสินค้า..."
            class="w-full pl-10 pr-4 py-2.5 border border-(--color-border) rounded-(--radius-md) bg-(--color-surface) text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
          />
        </div>
      </div>

      <!-- Product grid -->
      <div class="flex-1 overflow-y-auto">
        <!-- Loading -->
        <div
          v-if="loadingProducts"
          class="flex items-center justify-center h-40 text-(--color-text-muted)"
        >
          <svg
            class="animate-spin w-5 h-5 mr-2"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
          </svg>
          กำลังโหลด...
        </div>

        <!-- Empty state -->
        <div
          v-else-if="products.length === 0"
          class="flex flex-col items-center justify-center h-40 gap-2 text-(--color-text-muted)"
        >
          <svg
            class="w-10 h-10"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
            />
          </svg>
          <p class="text-sm">ไม่พบสินค้า</p>
        </div>

        <!-- Product cards -->
        <div
          v-else
          class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3"
        >
          <button
            v-for="product in products"
            :key="product.id"
            @click="openCustomize(product)"
            class="bg-(--color-surface) border border-(--color-border) rounded-(--radius-lg) overflow-hidden text-left hover:border-(--color-primary) hover:shadow-md transition-all group cursor-pointer"
          >
            <!-- Product image -->
            <div class="aspect-square bg-(--color-bg) overflow-hidden">
              <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
              />
              <div
                v-else
                class="w-full h-full flex items-center justify-center text-(--color-border)"
              >
                <svg
                  class="w-12 h-12"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
              </div>
            </div>
            <!-- Product info -->
            <div class="p-3">
              <p class="font-medium text-(--color-text) text-sm truncate">
                {{ product.name }}
              </p>
              <p class="text-(--color-primary) font-bold text-sm mt-0.5">
                ฿{{ formatMoney(product.price) }}
              </p>
            </div>
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex-shrink-0 py-1">
        <AppPagination
          :current-page="currentPage"
          :total-pages="totalPages"
          :total="totalProducts"
          :per-page="perPage"
          @change="onPageChange"
        />
      </div>
    </div>

    <!-- Right panel: Cart -->
    <div
      class="w-80 lg:w-96 flex flex-col bg-(--color-surface) border-l border-(--color-border) flex-shrink-0"
    >
      <!-- Cart header -->
      <div
        class="px-4 py-3.5 border-b border-(--color-border) flex items-center justify-between flex-shrink-0"
      >
        <h2 class="font-semibold text-(--color-text) flex items-center gap-2">
          <svg
            class="w-5 h-5 text-(--color-primary)"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
          ตะกร้า
        </h2>
        <span
          v-if="cartStore.totalItems > 0"
          class="text-xs bg-(--color-primary) text-white px-2 py-0.5 rounded-full font-medium"
        >
          {{ cartStore.totalItems }}
        </span>
      </div>

      <!-- Cart items list -->
      <div class="flex-1 overflow-y-auto p-4 space-y-3">
        <!-- Empty cart -->
        <div
          v-if="cartStore.items.length === 0"
          class="flex flex-col items-center justify-center h-full gap-3 text-(--color-text-muted)"
        >
          <svg
            class="w-12 h-12"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
          <p class="text-sm">ยังไม่มีสินค้าในตะกร้า</p>
          <p class="text-xs text-center">คลิกที่สินค้าเพื่อเพิ่มลงตะกร้า</p>
        </div>

        <!-- Cart item cards -->
        <div
          v-for="item in cartStore.items"
          :key="item.id"
          class="bg-(--color-bg) rounded-(--radius-md) p-3 border border-(--color-border)/50"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
              <p class="font-medium text-(--color-text) text-sm truncate">
                {{ item.product.name }}
              </p>
              <div class="text-xs text-(--color-text-muted) mt-1 space-y-0.5">
                <p v-if="item.size">ไซต์: {{ item.size.name }}</p>
                <p v-if="item.type">ประเภท: {{ item.type.name }}</p>
                <p v-if="item.toppings.length">
                  ท็อปปิ้ง: {{ item.toppings.map((t) => t.name).join(", ") }}
                </p>
              </div>
            </div>
            <button
              @click="cartStore.removeItem(item.id)"
              class="p-1 text-(--color-text-muted) hover:text-(--color-error) transition-colors flex-shrink-0 -mt-0.5"
              aria-label="ลบรายการ"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
          <div
            class="flex items-center justify-between mt-2 pt-2 border-t border-(--color-border)/50"
          >
            <span
              class="text-xs text-(--color-text-muted) bg-(--color-surface) px-2 py-0.5 rounded"
              >x{{ item.quantity }}</span
            >
            <span class="text-sm font-bold text-(--color-primary)"
              >฿{{ formatMoney(item.amount) }}</span
            >
          </div>
        </div>
      </div>

      <!-- Cart footer -->
      <div class="p-4 border-t border-(--color-border) flex-shrink-0 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-sm text-(--color-text-muted)">ยอดรวมทั้งหมด</span>
          <span class="text-xl font-bold text-(--color-text)"
            >฿{{ formatMoney(cartStore.totalAmount) }}</span
          >
        </div>
        <AppButton
          variant="primary"
          class="w-full"
          :disabled="cartStore.items.length === 0"
          @click="showPaymentModal = true"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
          ชำระเงิน
        </AppButton>
      </div>
    </div>
  </div>

  <!-- Product customization modal -->
  <AppModal
    v-model="showCustomizeModal"
    :title="selectedProduct?.name"
    size="md"
  >
    <div v-if="selectedProduct" class="space-y-5">
      <!-- Base price display -->
      <div
        class="flex items-center justify-between px-4 py-3 bg-(--color-bg) rounded-(--radius-md)"
      >
        <span class="text-sm text-(--color-text-muted)">ราคาเริ่มต้น</span>
        <span class="font-semibold text-(--color-text)"
          >฿{{ formatMoney(selectedProduct.price) }}</span
        >
      </div>

      <!-- Size selector -->
      <div v-if="allSizes.length > 0">
        <p class="text-sm font-medium text-(--color-text) mb-2.5">ไซต์</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="size in allSizes"
            :key="size.id"
            @click="selectedSize = size"
            :class="[
              'px-4 py-2 rounded-(--radius-md) text-sm font-medium border transition-all',
              selectedSize?.id === size.id
                ? 'bg-(--color-primary) text-white border-(--color-primary) shadow-sm'
                : 'bg-(--color-surface) text-(--color-text) border-(--color-border) hover:border-(--color-primary)/50',
            ]"
          >
            {{ size.name }}
            <span v-if="size.price > 0" class="text-xs opacity-75 ml-1"
              >+฿{{ formatMoney(size.price) }}</span
            >
          </button>
        </div>
      </div>

      <!-- Type selector -->
      <div v-if="allTypes.length > 0">
        <p class="text-sm font-medium text-(--color-text) mb-2.5">ประเภท</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="type in allTypes"
            :key="type.id"
            @click="selectedType = type"
            :class="[
              'px-4 py-2 rounded-(--radius-md) text-sm font-medium border transition-all',
              selectedType?.id === type.id
                ? 'bg-(--color-primary) text-white border-(--color-primary) shadow-sm'
                : 'bg-(--color-surface) text-(--color-text) border-(--color-border) hover:border-(--color-primary)/50',
            ]"
          >
            {{ type.name }}
            <span v-if="type.price > 0" class="text-xs opacity-75 ml-1"
              >+฿{{ formatMoney(type.price) }}</span
            >
          </button>
        </div>
      </div>

      <!-- Toppings checkboxes -->
      <div v-if="allToppings.length > 0">
        <p class="text-sm font-medium text-(--color-text) mb-2.5">ท็อปปิ้ง</p>
        <div class="grid grid-cols-2 gap-2">
          <label
            v-for="topping in allToppings"
            :key="topping.id"
            :class="[
              'flex items-center gap-2.5 p-2.5 rounded-(--radius-md) border cursor-pointer transition-all',
              isToppingSelected(topping)
                ? 'bg-(--color-primary-light) border-(--color-primary)'
                : 'bg-(--color-surface) border-(--color-border) hover:border-(--color-primary)/40',
            ]"
          >
            <input
              type="checkbox"
              :checked="isToppingSelected(topping)"
              @change="toggleTopping(topping)"
              class="w-4 h-4 accent-(--color-primary) rounded flex-shrink-0"
            />
            <span class="text-sm text-(--color-text) flex-1 min-w-0 truncate">{{
              topping.name
            }}</span>
            <span class="text-xs text-(--color-text-muted) flex-shrink-0"
              >+฿{{ formatMoney(topping.price) }}</span
            >
          </label>
        </div>
      </div>

      <!-- Quantity selector -->
      <div>
        <p class="text-sm font-medium text-(--color-text) mb-2.5">จำนวน</p>
        <div class="flex items-center gap-4">
          <button
            @click="quantity = Math.max(1, quantity - 1)"
            class="w-9 h-9 flex items-center justify-center rounded-(--radius-md) border border-(--color-border) bg-(--color-bg) hover:bg-(--color-border)/60 transition-colors text-(--color-text) text-lg font-medium"
          >
            −
          </button>
          <span
            class="w-10 text-center text-lg font-semibold text-(--color-text)"
            >{{ quantity }}</span
          >
          <button
            @click="quantity++"
            class="w-9 h-9 flex items-center justify-center rounded-(--radius-md) border border-(--color-border) bg-(--color-bg) hover:bg-(--color-border)/60 transition-colors text-(--color-text) text-lg font-medium"
          >
            +
          </button>
        </div>
      </div>

      <!-- Total price summary -->
      <div
        class="flex items-center justify-between px-4 py-3 bg-(--color-primary-light) rounded-(--radius-md)"
      >
        <span class="font-semibold text-(--color-text)">ราคารวม</span>
        <span class="text-xl font-bold text-(--color-primary)"
          >฿{{ formatMoney(itemTotal) }}</span
        >
      </div>
    </div>

    <template #footer>
      <AppButton variant="secondary" @click="showCustomizeModal = false"
        >ยกเลิก</AppButton
      >
      <AppButton variant="primary" @click="addToCart">
        <svg
          class="w-4 h-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        เพิ่มในตะกร้า
      </AppButton>
    </template>
  </AppModal>

  <!-- Payment modal -->
  <AppModal v-model="showPaymentModal" title="ชำระเงิน" size="md">
    <div class="space-y-5">
      <!-- Order items summary -->
      <div
        class="border border-(--color-border) rounded-(--radius-md) overflow-hidden"
      >
        <div
          class="bg-(--color-bg) px-4 py-2 text-xs font-medium text-(--color-text-muted) uppercase tracking-wider"
        >
          รายการสินค้า
        </div>
        <div class="max-h-44 overflow-y-auto divide-y divide-(--color-border)">
          <div
            v-for="item in cartStore.items"
            :key="item.id"
            class="flex items-center justify-between px-4 py-2.5 text-sm"
          >
            <div class="flex-1 min-w-0 mr-3">
              <p class="text-(--color-text) font-medium truncate">
                {{ item.product.name }}
              </p>
              <p class="text-xs text-(--color-text-muted)">
                {{
                  [item.size?.name, item.type?.name].filter(Boolean).join(" / ")
                }}
                <span v-if="item.toppings.length">
                  + {{ item.toppings.map((t) => t.name).join(", ") }}</span
                >
              </p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="text-xs text-(--color-text-muted)">
                x{{ item.quantity }}
              </p>
              <p class="font-semibold text-(--color-text)">
                ฿{{ formatMoney(item.amount) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment calculation -->
      <div class="space-y-4">
        <!-- Total -->
        <div
          class="flex items-center justify-between pb-3 border-b border-(--color-border)"
        >
          <span class="font-semibold text-(--color-text)">ยอดรวมทั้งหมด</span>
          <span class="text-2xl font-bold text-(--color-text)"
            >฿{{ formatMoney(cartStore.totalAmount) }}</span
          >
        </div>

        <!-- Received amount input -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            รับเงินมา <span class="text-(--color-error)">*</span>
          </label>
          <MoneyInput
            v-model="receivedAmount"
            placeholder="กรอกจำนวนเงิน"
            required
          />
        </div>

        <!-- Change amount -->
        <div
          :class="[
            'flex items-center justify-between px-4 py-3.5 rounded-(--radius-md)',
            canConfirmPayment
              ? 'bg-(--color-success-light) border border-(--color-success-border)'
              : 'bg-(--color-bg) border border-(--color-border)',
          ]"
        >
          <span
            :class="[
              'font-medium',
              canConfirmPayment
                ? 'text-(--color-success)'
                : 'text-(--color-text-muted)',
            ]"
          >
            เงินทอน
          </span>
          <span
            :class="[
              'text-xl font-bold',
              canConfirmPayment
                ? 'text-(--color-success)'
                : 'text-(--color-text-muted)',
            ]"
          >
            ฿{{ formatMoney(changeAmount) }}
          </span>
        </div>
      </div>
    </div>

    <template #footer>
      <AppButton variant="secondary" @click="showPaymentModal = false"
        >ยกเลิก</AppButton
      >
      <AppButton
        variant="primary"
        :loading="isSubmitting"
        :disabled="!canConfirmPayment"
        @click="confirmPayment"
      >
        ยืนยันการชำระเงิน
      </AppButton>
    </template>
  </AppModal>
</template>
