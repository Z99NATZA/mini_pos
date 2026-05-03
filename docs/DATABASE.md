# 🗄️ Database Schema

Database: **PostgreSQL 16**

---

## Tables

### `users`
ผู้ใช้งานในระบบ

| Column | Type | คำอธิบาย |
|--------|------|----------|
| `id` | SERIAL PK | Auto increment |
| `username` | VARCHAR(50) UNIQUE | ชื่อผู้ใช้สำหรับ login |
| `name` | VARCHAR(100) | ชื่อแสดง |
| `password` | VARCHAR(255) | bcrypt hash |
| `role` | VARCHAR(20) | `admin` หรือ `staff` |
| `image` | VARCHAR(255) | ชื่อไฟล์รูปโปรไฟล์ |
| `created_at` | TIMESTAMP | วันที่สร้าง |
| `updated_at` | TIMESTAMP | วันที่แก้ไขล่าสุด |

---

### `products`
สินค้าในระบบ

| Column | Type | คำอธิบาย |
|--------|------|----------|
| `id` | SERIAL PK | Auto increment |
| `name` | VARCHAR(100) UNIQUE | ชื่อสินค้า |
| `price` | DECIMAL(10,2) | ราคาฐาน |
| `image` | VARCHAR(255) | ชื่อไฟล์รูปสินค้า |
| `created_at` | TIMESTAMP | วันที่สร้าง |
| `updated_at` | TIMESTAMP | วันที่แก้ไขล่าสุด |

---

### `sizes`
ขนาดสินค้า (เช่น S, M, L)

| Column | Type | คำอธิบาย |
|--------|------|----------|
| `id` | SERIAL PK | Auto increment |
| `name` | VARCHAR(100) UNIQUE | ชื่อขนาด |
| `price` | DECIMAL(10,2) | ราคาเพิ่มจากฐาน |
| `created_at` | TIMESTAMP | วันที่สร้าง |
| `updated_at` | TIMESTAMP | วันที่แก้ไขล่าสุด |

---

### `types`
ประเภทสินค้า (เช่น ร้อน, เย็น, ปั่น)

| Column | Type | คำอธิบาย |
|--------|------|----------|
| `id` | SERIAL PK | Auto increment |
| `name` | VARCHAR(100) UNIQUE | ชื่อประเภท |
| `price` | DECIMAL(10,2) | ราคาเพิ่มจากฐาน |
| `created_at` | TIMESTAMP | วันที่สร้าง |
| `updated_at` | TIMESTAMP | วันที่แก้ไขล่าสุด |

---

### `toppings`
ท็อปปิ้ง (เช่น ไข่มุก, วุ้น)

| Column | Type | คำอธิบาย |
|--------|------|----------|
| `id` | SERIAL PK | Auto increment |
| `name` | VARCHAR(100) UNIQUE | ชื่อท็อปปิ้ง |
| `price` | DECIMAL(10,2) | ราคาต่อชิ้น |
| `created_at` | TIMESTAMP | วันที่สร้าง |
| `updated_at` | TIMESTAMP | วันที่แก้ไขล่าสุด |

---

### `orders`
ออเดอร์ที่สำเร็จแล้ว (คำสั่งซื้อ)

| Column | Type | คำอธิบาย |
|--------|------|----------|
| `id` | SERIAL PK | Auto increment |
| `order_number` | VARCHAR(20) UNIQUE | เลขออเดอร์ (YYYYMMDD + 5 หลัก) |
| `cashier_name` | VARCHAR(100) | ชื่อแคชเชียร์ |
| `total_amount` | DECIMAL(12,2) | ยอดรวมทั้งหมด |
| `received_amount` | DECIMAL(12,2) | เงินที่รับมาจากลูกค้า |
| `change_amount` | DECIMAL(12,2) | เงินทอน |
| `created_at` | TIMESTAMP | วันเวลาที่ขาย |

---

### `order_items`
รายการสินค้าในแต่ละออเดอร์

| Column | Type | คำอธิบาย |
|--------|------|----------|
| `id` | SERIAL PK | Auto increment |
| `order_id` | INTEGER FK → orders.id | อ้างอิงออเดอร์ (CASCADE DELETE) |
| `order_item_code` | VARCHAR(30) | รหัส item ภายในออเดอร์ |
| `product_name` | VARCHAR(100) | ชื่อสินค้า ณ เวลาขาย (snapshot) |
| `product_price` | DECIMAL(10,2) | ราคาสินค้า ณ เวลาขาย |
| `size_name` | VARCHAR(100) | ชื่อไซต์ ณ เวลาขาย |
| `size_price` | DECIMAL(10,2) | ราคาไซต์ ณ เวลาขาย |
| `type_name` | VARCHAR(100) | ชื่อประเภท ณ เวลาขาย |
| `type_price` | DECIMAL(10,2) | ราคาประเภท ณ เวลาขาย |
| `quantity` | INTEGER | จำนวน |
| `amount` | DECIMAL(12,2) | ราคารวมของ item นี้ |
| `created_at` | TIMESTAMP | วันเวลา |

> หมายเหตุ: ข้อมูล product/size/type เก็บเป็น snapshot (ชื่อ+ราคา ณ เวลาขาย) เพื่อไม่ให้ประวัติการขายเปลี่ยนแปลงเมื่อแก้ไขสินค้า

---

### `order_item_toppings`
ท็อปปิ้งของแต่ละ order item

| Column | Type | คำอธิบาย |
|--------|------|----------|
| `id` | SERIAL PK | Auto increment |
| `order_id` | INTEGER FK → orders.id | CASCADE DELETE |
| `order_item_code` | VARCHAR(30) | อ้างอิงกับ order_items |
| `topping_name` | VARCHAR(100) | ชื่อท็อปปิ้ง ณ เวลาขาย |
| `topping_price` | DECIMAL(10,2) | ราคาท็อปปิ้ง ณ เวลาขาย |
| `created_at` | TIMESTAMP | วันเวลา |

---

## Relationships

```
users
  (ไม่มี FK ออก)

products
  (ไม่มี FK ออก)

sizes / types / toppings
  (ไม่มี FK ออก)

orders
  └── order_items (order_id → orders.id CASCADE DELETE)
       └── order_item_toppings (order_id → orders.id CASCADE DELETE)
               (order_item_code เชื่อมกับ order_items.order_item_code)
```

---

## Format เลขออเดอร์

```
order_number = YYYYMMDD + NNNNN
เช่น: 2025061500001 (15 มิ.ย. 2025, ออเดอร์แรกของวัน)
      2025061500002 (15 มิ.ย. 2025, ออเดอร์ที่สอง)
      2025061600001 (16 มิ.ย. 2025, เริ่มนับใหม่)
```

---

## Files ที่ Upload

| ประเภท | โฟลเดอร์ | URL |
|--------|---------|-----|
| รูปสินค้า | `backend/public/uploads/products/` | `/uploads/products/{filename}` |
| รูปผู้ใช้ | `backend/public/uploads/users/` | `/uploads/users/{filename}` |
