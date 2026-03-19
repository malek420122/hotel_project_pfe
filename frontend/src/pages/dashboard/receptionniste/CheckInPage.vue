<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ $t('dashboard.check_in_title') }}</h2>
    <div class="card mb-6">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.search_reservation') }}</h3>
      <div class="flex gap-3">
        <input v-model="search" placeholder="Nom, email ou référence..." class="input-field flex-1" @keyup.enter="searchReservation" />
        <button @click="searchReservation" class="btn-primary">{{ $t('dashboard.btn_search') }}</button>
      </div>
    </div>
    <div v-if="found" class="card border-2 border-secondary mb-6">
      <h3 class="text-lg font-bold text-secondary mb-4">{{ $t('dashboard.reservation_found') }}</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
        <div><p class="text-xs text-gray-500">Client</p><p class="font-bold">{{ found.client }}</p></div>
        <div><p class="text-xs text-gray-500">Hôtel / Chambre</p><p class="font-bold">{{ found.hotel }} · {{ found.chambre }}</p></div>
        <div><p class="text-xs text-gray-500">Arrivée → Départ</p><p class="font-bold">{{ found.arrivee }} → {{ found.depart }}</p></div>
        <div><p class="text-xs text-gray-500">Voyageurs</p><p class="font-bold">{{ found.nbVoyageurs }}</p></div>
        <div><p class="text-xs text-gray-500">Montant</p><p class="font-bold text-secondary">{{ found.prix }}€</p></div>
        <div><p class="text-xs text-gray-500">Statut paiement</p><StatusBadge :status="found.paiement" /></div>
      </div>
      <div v-if="found.demandes" class="bg-yellow-50 rounded-xl p-3 mb-4">
        <p class="text-sm font-semibold text-yellow-800">📋 Demandes spéciales</p>
        <p class="text-sm text-yellow-700">{{ found.demandes }}</p>
      </div>
      <button @click="doCheckIn" class="btn-primary">🔑 Effectuer le Check-In</button>
    </div>
    <div class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Check-ins du jour</h3>
      <div class="space-y-2">
        <div v-for="r in todayList" :key="r.ref" class="flex items-center justify-between p-3 rounded-xl border hover:bg-gray-50">
          <div>
            <p class="font-medium text-gray-800">{{ r.client }}</p>
            <p class="text-sm text-gray-500">{{ r.chambre }} · {{ r.heure }}</p>
          </div>
          <div class="flex items-center gap-3">
            <StatusBadge :status="r.statut" />
            <button v-if="r.statut !== 'EN_COURS'" @click="found=r" class="btn-primary text-xs py-1.5 px-3">{{ $t('dashboard.check_in_title') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import StatusBadge from '../../../components/StatusBadge.vue'
const search = ref('')
const found = ref(null)
const todayList = ref([
  { ref: 'R001', client: 'Sophie Martin', chambre: '201', heure: '14:00', statut: 'CONFIRMEE', hotel: 'Atlas', arrivee: 'Auj.', depart: '15 Mar', nbVoyageurs: 2, prix: 450, paiement: 'PAYE' },
  { ref: 'R002', client: 'Ahmed Benali', chambre: '305', heure: '14:30', statut: 'CONFIRMEE', hotel: 'Atlas', arrivee: 'Auj.', depart: '16 Mar', nbVoyageurs: 1, prix: 280, paiement: 'PAYE', demandes: 'Chambre calme, loin de l\'ascenseur' },
  { ref: 'R003', client: 'Laura Dupont', chambre: '112', heure: '15:00', statut: 'EN_COURS', hotel: 'Atlas', arrivee: 'Hier', depart: 'Auj.', nbVoyageurs: 2, prix: 190, paiement: 'PAYE' },
])
function searchReservation() {
  const r = todayList.value.find(r => r.client.toLowerCase().includes(search.value.toLowerCase()) || r.ref.includes(search.value))
  found.value = r || null
  if (!r) alert('Aucune réservation trouvée')
}
function doCheckIn() { alert(`Check-in effectué pour ${found.value.client} !`); found.value = null }
</script>
