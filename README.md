# دفتر (Daftar)

نظام ERP / POS عربي مبني على Laravel 12 و Filament 4 — إدارة مبيعات، مشتريات، مخزون، ونقطة بيع.

## المتطلبات

- PHP 8.2+
- Composer
- Node.js (لأصول الواجهة إن لزم)

## التثبيت

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

بيانات الدخول الافتراضية بعد الـ seed:

- البريد: `admin@daftar.test`
- كلمة المرور: `password`

## صور المنتجات

بعد الـ seed تُنشأ صور توضيحية للمنتجات في `public/images/products`. يمكن رفع صور جديدة من شاشة المنتج (JPEG / PNG / WebP).
