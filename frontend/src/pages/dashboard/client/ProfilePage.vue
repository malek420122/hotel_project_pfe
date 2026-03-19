<template>
  <div class="max-w-xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Mon Profil</h2>
    <div class="card">
      <div class="flex items-center gap-4 mb-6">
        <div class="w-20 h-20 bg-secondary rounded-full flex items-center justify-center text-white text-3xl font-bold">
          {{ auth.user?.prenom?.[0] || 'U' }}
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-800">{{ auth.user?.prenom }} {{ auth.user?.nom }}</h3>
          <p class="text-gray-500">{{ auth.user?.email }}</p>
          <span class="text-xs bg-secondary text-white px-3 py-1 rounded-full mt-1 inline-block">Client</span>
        </div>
      </div>
      <form @submit.prevent="save" class="space-y-4">
        <p v-if="message" :class="['text-sm', message.type === 'success' ? 'text-green-600' : 'text-red-500']">
          {{ message.text }}
        </p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Prénom</label>
            <input v-model="form.prenom" class="input-field" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Nom</label>
            <input v-model="form.nom" class="input-field" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">Email</label>
          <input v-model="form.email" type="email" class="input-field" readonly />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">Téléphone</label>
          <input v-model="form.telephone" class="input-field" />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">Nouveau mot de passe (optionnel)</label>
          <input v-model="form.password" type="password" class="input-field" placeholder="Laisser vide pour ne pas changer" />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">Confirmer le mot de passe</label>
          <input v-model="form.passwordConfirmation" type="password" class="input-field" placeholder="Confirmez le mot de passe" />
        </div>
        <button type="submit" class="btn-primary w-full">Enregistrer les modifications</button>
      </form>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref } from 'vue'
import { useAuthStore } from '../../../stores/auth'
import api from '../../../api'
const auth = useAuthStore()
const message = ref(null)
const form = reactive({
  prenom: auth.user?.prenom || '',
  nom: auth.user?.nom || '',
  email: auth.user?.email || '',
  telephone: auth.user?.telephone || '',
  password: '',
  passwordConfirmation: '',
})
async function save() {
  try {
    if (form.password && form.password !== form.passwordConfirmation) {
      message.value = { type: 'error', text: 'Les mots de passe ne correspondent pas.' }
      return
    }
    message.value = null
    const payload = {
      prenom: form.prenom,
      nom: form.nom,
      telephone: form.telephone,
    }
    if (form.password) {
      payload.password = form.password
      payload.password_confirmation = form.passwordConfirmation
    }
    const { data } = await api.put('/profile', payload)
    auth.updateUser(data)
    message.value = { type: 'success', text: 'Profil mis à jour !' }
    form.password = ''
    form.passwordConfirmation = ''
  } catch (e) {
    message.value = { type: 'error', text: e.response?.data?.message || 'Erreur lors de la mise à jour.' }
  }
}
</script>
