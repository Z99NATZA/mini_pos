export function formatMoney(value: number | string): string {
  const num = typeof value === 'string' ? parseFloat(value) || 0 : value
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num)
}

// Remove commas and non-numeric characters except decimal point
export function parseMoney(value: string): string {
  return value.replace(/[^0-9.]/g, '').replace(/\.(?=.*\.)/g, '')
}

export function toFloat(value: string | number): number {
  if (typeof value === 'number') return value
  const clean = parseMoney(value)
  return parseFloat(clean) || 0
}
