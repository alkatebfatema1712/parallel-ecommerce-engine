# خطة اختبار JMeter المطلوبة

## الهدف
محاكاة 100 مستخدم متزامن على أهم عمليات النظام:

- عرض المنتجات الشائعة من cache.
- البحث عن منتج.
- عملية ثقيلة لإثبات إدارة الموارد.
- checkout لتأكيد الطلب والدفع.
- batch report.
- metrics.

## طريقة التشغيل

1. شغل Laravel:
```bash
php artisan serve --port=8000
```

2. شغل queue worker:
```bash
php artisan queue:work --tries=3 --timeout=60
```

3. افتح JMeter واستورد الملف:
```text
PROJECT_DELIVERY/jmeter/DreamStore_Parallel_100Users.jmx
```

4. عدّل المتغيرات من User Defined Variables:

- `BASE_URL = http://127.0.0.1:8000`
- `TOKEN = ضع توكن Sanctum هنا`
- `ORDER_ID = رقم طلب موجود pending`
- `PRODUCT_ID = رقم منتج موجود`

5. شغل الاختبار وخذ screenshots من:

- Summary Report
- View Results Tree
- Aggregate Report

## ماذا نثبت؟

- قبل الحل: احتمال تضارب في المخزون عند تأكيد نفس الطلب/المنتج من عدة مستخدمين.
- بعد الحل: لا يصبح المخزون سالباً، والطلب لا يُؤكد مرتين، والفاتورة تتولد بالخلفية.
- عند ضغط 100 مستخدم: النظام لا ينهار، والطلبات الزائدة تأخذ 429 بدل إسقاط السيرفر.
