const fs = require('fs');
const path = require('path');

const frPath = path.join(__dirname, 'src/i18n/fr.json');
const enPath = path.join(__dirname, 'src/i18n/en.json');
const arPath = path.join(__dirname, 'src/i18n/ar.json');

const fr = JSON.parse(fs.readFileSync(frPath, 'utf8'));
const en = JSON.parse(fs.readFileSync(enPath, 'utf8'));
const ar = JSON.parse(fs.readFileSync(arPath, 'utf8'));

fr.dashboard = fr.dashboard || {};
en.dashboard = en.dashboard || {};
ar.dashboard = ar.dashboard || {};

const translations = [
    { fr: "Modération des Avis", en: "Review Moderation", ar: "الإشراف على التقييمات", key: "dashboard.review_moderation" },
    { fr: "✅ Publier", en: "✅ Publish", ar: "✅ نشر", key: "dashboard.publish" },
    { fr: "🚫 Rejeter", en: "🚫 Reject", ar: "🚫 رفض", key: "dashboard.reject" },
    { fr: "💬 Répondre", en: "💬 Reply", ar: "💬 رد", key: "dashboard.reply" },
    { fr: "Statistiques", en: "Statistics", ar: "الإحصائيات", key: "dashboard.statistics" },
    { fr: "Réservations sur 12 mois", en: "12-month Reservations", ar: "حجوزات 12 شهراً", key: "dashboard.reservations_12_months" },
    { fr: "Revenus par hotel", en: "Revenue by hotel", ar: "الإيرادات حسب الفندق", key: "dashboard.revenue_by_hotel" },
    { fr: "Sources de trafic", en: "Traffic sources", ar: "مصادر الزيارات", key: "dashboard.traffic_sources" },
    { fr: "Taux d'occupation mensuel", en: "Monthly occupancy rate", ar: "معدل الإشغال الشهري", key: "dashboard.monthly_occupancy" },
    { fr: "Check-In", en: "Check-In", ar: "تسجيل الوصول", key: "dashboard.check_in_title" },
    { fr: "Rechercher une réservation", en: "Search a reservation", ar: "البحث عن حجز", key: "dashboard.search_reservation" },
    { fr: "✅ Réservation trouvée", en: "✅ Reservation found", ar: "✅ تم العثور على الحجز", key: "dashboard.reservation_found" },
    { fr: "Codes Promo", en: "Promo Codes", ar: "الرموز الترويجية", key: "dashboard.promo_codes" },
    { fr: "+ Créer un code", en: "+ Create code", ar: "+ إنشاء رمز", key: "dashboard.create_code" },
    { fr: "📋 Copier", en: "📋 Copy", ar: "📋 نسخ", key: "dashboard.copy" },
    { fr: "Nouveau code promo", en: "New promo code", ar: "رمز ترويجي جديد", key: "dashboard.new_promo_code" },
    { fr: "Pourcentage (%)", en: "Percentage (%)", ar: "النسبة المئوية (%)", key: "dashboard.percentage" },
    { fr: "Montant fixe (€)", en: "Fixed amount (€)", ar: "مبلغ ثابت (€)", key: "dashboard.fixed_amount" },
    { fr: "Nb max utilisations", en: "Max uses", ar: "أقصى عدد للاستخدام", key: "dashboard.nb_max_uses" },
    { fr: "+ Nouvelle promotion", en: "+ New promotion", ar: "+ عرض ترويجي جديد", key: "dashboard.plus_new_promotion" },
    { fr: "Nouveaux clients", en: "New clients", ar: "عملاء جدد", key: "dashboard.new_clients" },
    { fr: "Membres Fidèles", en: "Loyal members", ar: "أعضاء أوفياء", key: "dashboard.loyal_members" },
    { fr: "Points attribués", en: "Points awarded", ar: "نقاط ممنوحة", key: "dashboard.points_awarded" },
    { fr: "Top membres fidèles", en: "Top loyal members", ar: "أفضل الأعضاء الأوفياء", key: "dashboard.top_loyal_members" },
    { fr: "Conversions par canal", en: "Conversions by channel", ar: "التحويلات حسب القناة", key: "dashboard.conversions_channel" },
    { fr: "Efficacité des promotions", en: "Promotions efficiency", ar: "فعالية العروض الترويجية", key: "dashboard.promotions_efficiency" },
    { fr: "Avis récents", en: "Recent reviews", ar: "التقييمات الأخيرة", key: "dashboard.recent_reviews" },
    { fr: "Vue d'ensemble Marketing", en: "Marketing Overview", ar: "نظرة عامة على التسويق", key: "dashboard.marketing_overview" },
    { fr: "Liste des réservations", en: "Reservations list", ar: "قائمة الحجوزات", key: "dashboard.reservations_list" },
    { fr: "Rechercher", en: "Search", ar: "بحث", key: "dashboard.btn_search" }
];

translations.forEach(t => {
   const parts = t.key.split('.');
   fr[parts[0]][parts[1]] = t.fr;
   en[parts[0]][parts[1]] = t.en;
   ar[parts[0]][parts[1]] = t.ar;
});

fs.writeFileSync(frPath, JSON.stringify(fr, null, 2));
fs.writeFileSync(enPath, JSON.stringify(en, null, 2));
fs.writeFileSync(arPath, JSON.stringify(ar, null, 2));

function processDirectory(directory) {
    const files = fs.readdirSync(directory);
    for (const file of files) {
        const fullPath = path.join(directory, file);
        if (fs.statSync(fullPath).isDirectory()) {
            processDirectory(fullPath);
        } else if (fullPath.endsWith('.vue')) {
            let content = fs.readFileSync(fullPath, 'utf8');
            let modified = false;
            
            translations.forEach(t => {
                const escapedFr = t.fr.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                
                // replace >Text<
                const regex1 = new RegExp(`>\\s*${escapedFr}\\s*<`, 'g');
                if (regex1.test(content)) {
                    content = content.replace(regex1, `>{{ $t('${t.key}') }}<`);
                    modified = true;
                }
                
                // replace placeholder="Text"
                const regex2 = new RegExp(`placeholder="${escapedFr}"`, 'g');
                if (regex2.test(content)) {
                    content = content.replace(regex2, `:placeholder="$t('${t.key}')"`);
                    modified = true;
                }
            });

            if (modified) {
                fs.writeFileSync(fullPath, content, 'utf8');
                console.log(`Updated ${fullPath}`);
            }
        }
    }
}

processDirectory(path.join(__dirname, 'src/pages'));
processDirectory(path.join(__dirname, 'src/components'));
