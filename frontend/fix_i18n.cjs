const fs = require('fs');
const path = require('path');

const frPath = path.join(__dirname, 'src/i18n/fr.json');
const enPath = path.join(__dirname, 'src/i18n/en.json');

const fr = JSON.parse(fs.readFileSync(frPath, 'utf8'));
const en = JSON.parse(fs.readFileSync(enPath, 'utf8'));

fr.dashboard = fr.dashboard || {};
en.dashboard = en.dashboard || {};

const translations = [
    { fr: "Vue d'ensemble", en: "Overview", key: "dashboard.overview_title" },
    { fr: "Total Hôtels", en: "Total Hotels", key: "dashboard.total_hotels" },
    { fr: "Réservations (mois)", en: "Reservations (month)", key: "dashboard.reservations_month" },
    { fr: "Revenus (mois)", en: "Revenue (month)", key: "dashboard.revenue_month" },
    { fr: "Utilisateurs actifs", en: "Active Users", key: "dashboard.active_users" },
    { fr: "Réservations par mois", en: "Reservations per month", key: "dashboard.reservations_chart" },
    { fr: "Répartition revenus", en: "Revenue distribution", key: "dashboard.revenue_chart" },
    { fr: "Réservations récentes", en: "Recent reservations", key: "dashboard.recent_reservations" },
    { fr: "Meilleurs hôtels", en: "Top hotels", key: "dashboard.top_hotels" },
    { fr: "Promotions", en: "Promotions", key: "dashboard.promotions" },
    { fr: "Nouvelle promotion", en: "New promotion", key: "dashboard.new_promotion" },
    { fr: "Titre de la promotion", en: "Promotion title", key: "dashboard.promo_title" },
    { fr: "Description", en: "Description", key: "dashboard.description" },
    { fr: "Remise %", en: "Discount %", key: "dashboard.discount" },
    { fr: "Utilisations max", en: "Max uses", key: "dashboard.max_uses" },
    { fr: "Annuler", en: "Cancel", key: "common.cancel" },
    { fr: "Créer", en: "Create", key: "common.create" },
    { fr: "Nouvelle réservation", en: "New Booking", key: "dashboard.new_booking" },
    { fr: "Choisir un hôtel et une chambre", en: "Choose a hotel and room", key: "dashboard.choose_room" },
    { fr: "Destination", en: "Destination", key: "dashboard.destination" },
    { fr: "Ville...", en: "City...", key: "dashboard.city_placeholder" },
    { fr: "Arrivée", en: "Check-in", key: "dashboard.checkin" },
    { fr: "Départ", en: "Check-out", key: "dashboard.checkout" },
    { fr: "Rechercher les chambres", en: "Search rooms", key: "dashboard.search_rooms" },
    { fr: "Suivant →", en: "Next →", key: "dashboard.next" },
    { fr: "← Retour", en: "← Back", key: "dashboard.back" },
    { fr: "Détails du séjour", en: "Stay details", key: "dashboard.stay_details" },
    { fr: "Nombre de voyageurs", en: "Number of guests", key: "dashboard.guests_count" },
    { fr: "Demandes spéciales", en: "Special requests", key: "dashboard.special_requests" },
    { fr: "Services supplémentaires", en: "Additional services", key: "dashboard.additional_services" },
    { fr: "Paiement", en: "Payment", key: "dashboard.payment" },
    { fr: "Code promo", en: "Promo code", key: "dashboard.promo_code" },
    { fr: "Appliquer", en: "Apply", key: "dashboard.apply" },
    { fr: "Mes réservations", en: "My reservations", key: "dashboard.my_reservations" },
    { fr: "Tous les statuts", en: "All statuses", key: "dashboard.all_statuses" },
    { fr: "Référence", en: "Reference", key: "dashboard.reference" },
    { fr: "Hôtel", en: "Hotel", key: "dashboard.hotel" },
    { fr: "Dates", en: "Dates", key: "dashboard.dates" },
    { fr: "Prix", en: "Price", key: "dashboard.price" },
    { fr: "Statut", en: "Status", key: "dashboard.status" },
    { fr: "Actions", en: "Actions", key: "dashboard.actions" },
];

translations.forEach(t => {
   const parts = t.key.split('.');
   if (parts[0] === 'common') {
       fr.common[parts[1]] = t.fr;
       en.common[parts[1]] = t.en;
   } else {
       fr.dashboard[parts[1]] = t.fr;
       en.dashboard[parts[1]] = t.en;
   }
});

fs.writeFileSync(frPath, JSON.stringify(fr, null, 2));
fs.writeFileSync(enPath, JSON.stringify(en, null, 2));

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
                // escape special regex chars for fr
                const escapedFr = t.fr.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                
                // replace >Text< -> >{{ $t('key') }}<
                const regex1 = new RegExp(`>\\s*${escapedFr}\\s*<`, 'g');
                if (regex1.test(content)) {
                    content = content.replace(regex1, `>{{ $t('${t.key}') }}<`);
                    modified = true;
                }
                
                // replace placeholder="Text" -> :placeholder="$t('key')"
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
