const fs = require('fs');
const path = require('path');

const arPath = path.join(__dirname, 'src/i18n/ar.json');
const ar = JSON.parse(fs.readFileSync(arPath, 'utf8'));

ar.dashboard = ar.dashboard || {};

const translations = [
    { ar: "نظرة عامة", key: "dashboard.overview_title" },
    { ar: "إجمالي الفنادق", key: "dashboard.total_hotels" },
    { ar: "الحجوزات (شهرياً)", key: "dashboard.reservations_month" },
    { ar: "الإيرادات (شهرياً)", key: "dashboard.revenue_month" },
    { ar: "المستخدمون النشطون", key: "dashboard.active_users" },
    { ar: "الحجوزات شهرياً", key: "dashboard.reservations_chart" },
    { ar: "توزيع الإيرادات", key: "dashboard.revenue_chart" },
    { ar: "الحجوزات الأخيرة", key: "dashboard.recent_reservations" },
    { ar: "أفضل الفنادق", key: "dashboard.top_hotels" },
    { ar: "العروض الترويجية", key: "dashboard.promotions" },
    { ar: "عرض ترويجي جديد", key: "dashboard.new_promotion" },
    { ar: "عنوان العرض الترويجي", key: "dashboard.promo_title" },
    { ar: "الوصف", key: "dashboard.description" },
    { ar: "نسبة الخصم %", key: "dashboard.discount" },
    { ar: "أقصى عدد للاستخدام", key: "dashboard.max_uses" },
    { ar: "إلغاء", key: "common.cancel" },
    { ar: "إنشاء", key: "common.create" },
    { ar: "حجز جديد", key: "dashboard.new_booking" },
    { ar: "اختر فندقاً وغرفة", key: "dashboard.choose_room" },
    { ar: "الوجهة", key: "dashboard.destination" },
    { ar: "المدينة...", key: "dashboard.city_placeholder" },
    { ar: "تاريخ الوصول", key: "dashboard.checkin" },
    { ar: "تاريخ المغادرة", key: "dashboard.checkout" },
    { ar: "البحث عن غرف", key: "dashboard.search_rooms" },
    { ar: "التالي →", key: "dashboard.next" },
    { ar: "← رجوع", key: "dashboard.back" },
    { ar: "تفاصيل الإقامة", key: "dashboard.stay_details" },
    { ar: "عدد الضيوف", key: "dashboard.guests_count" },
    { ar: "طلبات خاصة", key: "dashboard.special_requests" },
    { ar: "خدمات إضافية", key: "dashboard.additional_services" },
    { ar: "الدفع", key: "dashboard.payment" },
    { ar: "رمز ترويجي", key: "dashboard.promo_code" },
    { ar: "تطبيق", key: "dashboard.apply" },
    { ar: "حجوزاتي", key: "dashboard.my_reservations" },
    { ar: "جميع الحالات", key: "dashboard.all_statuses" },
    { ar: "المرجع", key: "dashboard.reference" },
    { ar: "الفندق", key: "dashboard.hotel" },
    { ar: "التواريخ", key: "dashboard.dates" },
    { ar: "السعر", key: "dashboard.price" },
    { ar: "الحالة", key: "dashboard.status" },
    { ar: "الإجراءات", key: "dashboard.actions" }
];

translations.forEach(t => {
   const parts = t.key.split('.');
   if (parts[0] === 'common') {
       ar.common = ar.common || {};
       ar.common[parts[1]] = t.ar;
   } else {
       ar.dashboard[parts[1]] = t.ar;
   }
});

fs.writeFileSync(arPath, JSON.stringify(ar, null, 2));
console.log("Updated ar.json");
