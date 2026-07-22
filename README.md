# دفتر (Daftar)

نظام ERP / POS عربي مبني على Laravel 12 و Filament 4 — إدارة مبيعات، مشتريات، مخزون، ونقطة بيع.

## المتطلبات

- PHP 8.2+
- Composer
- Node.js (لأصول الواجهة إن لزم)

## التثبيت

```bash
composer install --no-interaction --prefer-dist
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

على السيرفر يُفضّل:

```bash
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
```

إذا فشل `migrate` على MySQL بسبب جداول موجودة جزئيًا (تثبيت أولي بلا بيانات مهمة):

```bash
php artisan migrate:fresh --seed
```

بيانات الدخول الافتراضية بعد الـ seed:

- البريد: `admin@daftar.test`
- كلمة المرور: `password`

## صور المنتجات

بعد الـ seed تُنشأ صور توضيحية للمنتجات في `public/images/products`. يمكن رفع صور جديدة من شاشة المنتج (JPEG / PNG / WebP).
