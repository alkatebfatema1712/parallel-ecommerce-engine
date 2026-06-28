# مشروع البرمجة المتوازية - Laravel E-Commerce Backend

هذا المجلد يشرح أين تم تطبيق كل متطلب من المتطلبات العشرة داخل المشروع.

## التشغيل المختصر

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan queue:work --tries=3 --timeout=60
php artisan serve --port=8000
```

يفضل تفعيل Redis في `.env`:

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=database
REDIS_HOST=127.0.0.1
CHECKOUT_MAX_PARALLEL=8
```

## أين المتطلبات؟

1. **Race Condition / Data Integrity**  
   الملف: `app/Services/CheckoutService.php`  
   تم استخدام `DB::transaction` و `lockForUpdate` و `version` على المنتج.

2. **Resource Management**  
   الملف: `app/Services/ResourceSemaphore.php`  
   endpoint: `GET /api/parallel/resource-heavy`

3. **Asynchronous Queue**  
   الملفات: `app/Jobs/GenerateInvoiceJob.php`, `app/Jobs/DailySalesReportJob.php`  
   الفاتورة لا تُولّد داخل request الرئيسي.

4. **Batch Processing**  
   الملف: `app/Jobs/DailySalesReportJob.php`  
   endpoint: `POST /api/parallel/batch/daily-sales`

5. **Load Balancing Simulation**  
   الملف: `app/Http/Controllers/LoadBalancerController.php`  
   endpoint: `GET /api/simulate/load-balancing`

6. **Caching Distributed**  
   الملف: `app/Http/Controllers/ParallelProjectController.php`  
   endpoints:  
   - `GET /api/parallel/products/popular`  
   - `GET /api/parallel/products/{id}/cached`  
   - `GET /api/parallel/search?query=...`

7. **Distributed Lock**  
   الملفات: `CheckoutService.php` و endpoint: `POST /api/parallel/lock-demo/{productId}`

8. **ACID Transaction**  
   الملف: `CheckoutService.php`  
   العملية المركبة: دفع + خصم مخزون + تأكيد طلب.

9. **Stress Testing 100 users**  
   الملف: `PROJECT_DELIVERY/jmeter/DreamStore_Parallel_100Users.jmx`

10. **Benchmarking / Bottleneck**  
   الملف: `app/Http/Middleware/PerformanceMonitor.php`  
   endpoint: `GET /api/parallel/metrics`

## الصور المطلوبة في التقرير

ضع الصور داخل:

`PROJECT_DELIVERY/screenshots/`

الأسماء المقترحة:

- `01_race_condition_before.png`
- `02_race_condition_after.png`
- `03_jmeter_100_users_summary.png`
- `04_resources_before.png`
- `05_resources_after.png`
- `06_cache_before_after.png`
- `07_metrics_bottleneck.png`
