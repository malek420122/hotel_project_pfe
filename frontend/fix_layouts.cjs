const fs = require('fs');
const path = require('path');

const frPath = path.join(__dirname, 'src/i18n/fr.json');
const enPath = path.join(__dirname, 'src/i18n/en.json');
const arPath = path.join(__dirname, 'src/i18n/ar.json');

const fr = JSON.parse(fs.readFileSync(frPath, 'utf8'));
const en = JSON.parse(fs.readFileSync(enPath, 'utf8'));
const ar = JSON.parse(fs.readFileSync(arPath, 'utf8'));

fr.nav = fr.nav || {}; en.nav = en.nav || {}; ar.nav = ar.nav || {};
fr.dashboard = fr.dashboard || {}; en.dashboard = en.dashboard || {}; ar.dashboard = ar.dashboard || {};

const translations = {
    'nav.overview': { fr: "Vue d'ensemble", en: "Overview", ar: "نظرة عامة" },
    'nav.promotions': { fr: "Promotions", en: "Promotions", ar: "العروض الترويجية" },
    'nav.promo_codes': { fr: "Codes promo", en: "Promo Codes", ar: "الرموز الترويجية" },
    'nav.statistics': { fr: "Statistiques", en: "Statistics", ar: "الإحصائيات" },
    'nav.reviews': { fr: "Modération avis", en: "Review Moderation", ar: "الإشراف على التقييمات" },
    'nav.loyalty': { fr: "Programme fidélité", en: "Loyalty Program", ar: "برنامج الولاء" },
    'nav.queue': { fr: "File d'attente", en: "Queue", ar: "طابور" },
    'nav.checkin': { fr: "Check-In", en: "Check-In", ar: "تسجيل الوصول" },
    'nav.checkout': { fr: "Check-Out", en: "Check-Out", ar: "تسجيل المغادرة" },
    'nav.room_grid': { fr: "Grille chambres", en: "Room Grid", ar: "شبكة الغرف" },
    'nav.special_requests': { fr: "Demandes spéciales", en: "Special Requests", ar: "طلبات خاصة" },
    'dashboard.active_promotions': { fr: "Promotions actives", en: "Active promotions", ar: "عروض ترويجية نشطة" },
    'dashboard.used_promo_codes': { fr: "Codes promo utilisés", en: "Used promo codes", ar: "رموز ترويجية مستخدمة" },
    'dashboard.avg_rating': { fr: "Note moyenne", en: "Average rating", ar: "متوسط التقييم" },
    'dashboard.conversion_rate': { fr: "Taux conversion", en: "Conversion rate", ar: "معدل التحويل" },
    'dashboard.bronze_members': { fr: "Membres Bronze", en: "Bronze members", ar: "أعضاء برونزية" },
    'dashboard.silver_members': { fr: "Membres Argent", en: "Silver members", ar: "أعضاء فضية" },
    'dashboard.gold_members': { fr: "Membres Or", en: "Gold members", ar: "أعضاء ذهبية" },
    'dashboard.waiting': { fr: "En attente", en: "Waiting", ar: "قيد الانتظار" },
    'dashboard.today_checkins': { fr: "Check-in aujourd'hui", en: "Check-ins today", ar: "تسجيل الوصول اليوم" },
    'dashboard.today_checkouts': { fr: "Check-out aujourd'hui", en: "Check-outs today", ar: "تسجيل المغادرة اليوم" },
    'dashboard.active_reservations': { fr: "Réservations actives", en: "Active reservations", ar: "حجوزات نشطة" },
    'dashboard.completed_stays': { fr: "Séjours complétés", en: "Completed stays", ar: "إقامات مكتملة" },
    'dashboard.loyalty_points': { fr: "Points fidélité", en: "Loyalty points", ar: "نقاط الولاء" },
    'dashboard.total_spent': { fr: "Total dépenses", en: "Total spent", ar: "إجمالي الإنفاق" }
};

for (const [key, t] of Object.entries(translations)) {
    const parts = key.split('.');
    fr[parts[0]][parts[1]] = t.fr;
    en[parts[0]][parts[1]] = t.en;
    ar[parts[0]][parts[1]] = t.ar;
}

fs.writeFileSync(frPath, JSON.stringify(fr, null, 2));
fs.writeFileSync(enPath, JSON.stringify(en, null, 2));
fs.writeFileSync(arPath, JSON.stringify(ar, null, 2));

// Update KPI Cards
function updateKpiCards() {
    const pagesDir = path.join(__dirname, 'src/pages/dashboard');
    const walk = (dir) => {
        fs.readdirSync(dir).forEach(file => {
            const fullPath = path.join(dir, file);
            if (fs.statSync(fullPath).isDirectory()) walk(fullPath);
            else if (fullPath.endsWith('.vue')) {
                let content = fs.readFileSync(fullPath, 'utf8');
                let modified = false;
                for (const [key, t] of Object.entries(translations)) {
                    const regex = new RegExp(`label="${t.fr}"`, 'g');
                    if (regex.test(content)) {
                        content = content.replace(regex, `:label="$t('${key}')"`);
                        modified = true;
                    }
                }
                if (content.match(/label="(Total Hôtels|Réservations \(mois\)|Revenus \(mois\)|Utilisateurs actifs)"/)) {
                    content = content.replace(/label="Total Hôtels"/g, `:label="$t('dashboard.total_hotels')"`);
                    content = content.replace(/label="Réservations \(mois\)"/g, `:label="$t('dashboard.reservations_month')"`);
                    content = content.replace(/label="Revenus \(mois\)"/g, `:label="$t('dashboard.revenue_month')"`);
                    content = content.replace(/label="Utilisateurs actifs"/g, `:label="$t('dashboard.active_users')"`);
                    modified = true;
                }
                if (modified) fs.writeFileSync(fullPath, content, 'utf8');
            }
        });
    };
    walk(pagesDir);
}
updateKpiCards();

// Update MarketingLayout
let mkLayoutPath = path.join(__dirname, 'src/layouts/MarketingLayout.vue');
if (fs.existsSync(mkLayoutPath)) {
    let content = fs.readFileSync(mkLayoutPath, 'utf8');
    content = content.replace(/<span>Déconnexion<\/span>/, `<span>{{ t('auth.logout') }}</span>`);
    if (!content.includes('import { useI18n } from \'vue-i18n\'')) {
        content = content.replace(/import { useAuthStore } from '\.\.\/stores\/auth'/, `import { useAuthStore } from '../stores/auth'\nimport { useI18n } from 'vue-i18n'`);
    }
    if (!content.includes('const { t } = useI18n()')) {
        content = content.replace(/const auth = useAuthStore\(\)/, `const auth = useAuthStore()\nconst { t } = useI18n()`);
    }
    content = content.replace(/const navItems = \[([\s\S]*?)\]\nconst pageTitle = computed\(\(\) => navItems\.find\(i => route\.path\.startsWith\(i\.to\)\)\?\.label \|\| 'Marketing'\)/, (match, p1) => {
        let items = p1.replace(/label: 'Vue d\\'ensemble'/g, `label: t('nav.overview')`)
                      .replace(/label: 'Promotions'/g, `label: t('nav.promotions')`)
                      .replace(/label: 'Codes promo'/g, `label: t('nav.promo_codes')`)
                      .replace(/label: 'Statistiques'/g, `label: t('nav.statistics')`)
                      .replace(/label: 'Modération avis'/g, `label: t('nav.reviews')`)
                      .replace(/label: 'Programme fidélité'/g, `label: t('nav.loyalty')`);
        return `const navItems = computed(() => [${items}])\nconst pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('nav.marketing'))`;
    });
    fs.writeFileSync(mkLayoutPath, content, 'utf8');
}

// Update ReceptionnisteLayout
let recLayoutPath = path.join(__dirname, 'src/layouts/ReceptionnisteLayout.vue');
if (fs.existsSync(recLayoutPath)) {
    let content = fs.readFileSync(recLayoutPath, 'utf8');
    content = content.replace(/<span>Déconnexion<\/span>/, `<span>{{ t('auth.logout') }}</span>`);
    if (!content.includes('import { useI18n } from \'vue-i18n\'')) {
        content = content.replace(/import { useAuthStore } from '\.\.\/stores\/auth'/, `import { useAuthStore } from '../stores/auth'\nimport { useI18n } from 'vue-i18n'`);
    }
    if (!content.includes('const { t } = useI18n()')) {
        content = content.replace(/const auth = useAuthStore\(\)/, `const auth = useAuthStore()\nconst { t } = useI18n()`);
    }
    content = content.replace(/const navItems = \[([\s\S]*?)\]\nconst pageTitle = computed\(\(\) => navItems\.find\(i => route\.path\.startsWith\(i\.to\)\)\?\.label \|\| 'Réception'\)/, (match, p1) => {
        let items = p1.replace(/label: 'File d\\'attente'/g, `label: t('nav.queue')`)
                      .replace(/label: 'Check-In'/g, `label: t('nav.checkin')`)
                      .replace(/label: 'Check-Out'/g, `label: t('nav.checkout')`)
                      .replace(/label: 'Grille chambres'/g, `label: t('nav.room_grid')`)
                      .replace(/label: 'Demandes spéciales'/g, `label: t('nav.special_requests')`);
        return `const navItems = computed(() => [${items}])\nconst pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('nav.reception'))`;
    });
    fs.writeFileSync(recLayoutPath, content, 'utf8');
}
console.log('Layouts and KPI cards updated!');
