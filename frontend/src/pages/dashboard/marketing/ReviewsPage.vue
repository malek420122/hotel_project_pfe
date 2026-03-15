<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Modération des Avis</h2>
    <div class="flex gap-3 mb-6">
      <button v-for="f in filters" :key="f.val" @click="active=f.val"
        :class="['px-4 py-2 rounded-xl text-sm font-medium', active===f.val ? 'bg-secondary text-white' : 'bg-white border text-gray-600 hover:bg-gray-50']">
        {{ f.label }} <span class="ml-1 bg-white/20 px-1 rounded">{{ f.count }}</span>
      </button>
    </div>
    <div class="space-y-4">
      <div v-for="avis in filteredAvis" :key="avis.id" class="card">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 bg-secondary rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">{{ avis.client[0] }}</div>
          <div class="flex-1">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-bold text-gray-800">{{ avis.client }}</p>
                <p class="text-sm text-gray-500">{{ avis.hotel }} · {{ avis.date }}</p>
              </div>
              <div class="flex gap-0.5">
                <span v-for="i in 5" :key="i" :class="i <= avis.note ? 'text-accent' : 'text-gray-200'">★</span>
              </div>
            </div>
            <p class="text-gray-700 mt-2 italic">"{{ avis.comment }}"</p>
            <div class="flex gap-2 mt-3">
              <button @click="publish(avis)" class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-700 hover:bg-green-100">✅ Publier</button>
              <button @click="reject(avis)" class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">🚫 Rejeter</button>
              <button class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100">💬 Répondre</button>
            </div>
          </div>
          <StatusBadge :status="avis.statut" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import StatusBadge from '../../../components/StatusBadge.vue'
const active = ref('EN_ATTENTE')
const filters = ref([
  { val:'EN_ATTENTE', label:'En attente', count:3 },
  { val:'PUBLIE', label:'Publiés', count:15 },
  { val:'REJETE', label:'Rejetés', count:2 },
])
const avis = ref([
  { id:1, client:'Sophie Martin', hotel:'Atlas', date:'10 Mar', note:5, comment:'Service 5 étoiles, je reviendrai !', statut:'EN_ATTENTE' },
  { id:2, client:'Ahmed Benali', hotel:'Riad', date:'11 Mar', note:2, comment:'Chambre décevante, pas conforme aux photos.', statut:'EN_ATTENTE' },
  { id:3, client:'Laura Dupont', hotel:'Atlas', date:'9 Mar', note:4, comment:'Excellent rapport qualité-prix.', statut:'EN_ATTENTE' },
  { id:4, client:'Jean Dupont', hotel:'Ibis', date:'8 Mar', note:5, comment:'Personnel très accueillant.', statut:'PUBLIE' },
])
const filteredAvis = computed(() => avis.value.filter(a => a.statut === active.value))
function publish(a) { a.statut = 'PUBLIE' }
function reject(a) { a.statut = 'REJETE' }
</script>
