<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-12" style="background: linear-gradient(135deg, #003580, #0071c2);">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg">
      <div class="text-center mb-6">
        <RouterLink to="/" class="text-2xl font-extrabold text-primary">🏨 HotelEase</RouterLink>
        <h1 class="text-xl font-bold text-gray-800 mt-2">{{ t('auth.registerTitle') }}</h1>
      </div>
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-xl p-3 text-red-700 text-sm mb-4">{{ error }}</div>
      <form @submit.prevent="handleRegister" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Prénom</label>
            <input v-model="form.prenom" type="text" placeholder="Prénom" class="input-field" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Nom</label>
            <input v-model="form.nom" type="text" placeholder="Nom" class="input-field" required />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">{{ t('auth.email') }}</label>
          <input v-model="form.email" type="email" :placeholder="t('auth.email')" class="input-field" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Téléphone</label>
          <input v-model="form.telephone" type="tel" placeholder="+33 6 12 34 56 78" class="input-field" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">{{ t('auth.password') }}</label>
          <div class="relative">
            <input v-model="form.password" :type="showPwd ? 'text' : 'password'" class="input-field pr-10" required minlength="8" />
            <button type="button" @click="showPwd = !showPwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">{{ showPwd ? '🙈' : '👁️' }}</button>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">{{ t('auth.confirmPassword') }}</label>
          <input v-model="form.password_confirmation" :type="showPwd ? 'text' : 'password'" class="input-field" required />
          <p v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation" class="text-xs text-red-500 mt-1">Les mots de passe ne correspondent pas</p>
        </div>
        <div class="flex items-start gap-2">
          <input v-model="form.accept" type="checkbox" required class="mt-0.5 rounded" id="accept" />
          <label for="accept" class="text-sm text-gray-600">J'accepte les <a href="#" class="text-secondary hover:underline">CGU</a> et la <a href="#" class="text-secondary hover:underline">politique de confidentialité</a></label>
        </div>
        <button type="submit" :disabled="loading || form.password !== form.password_confirmation"
          class="btn-primary w-full py-3 disabled:opacity-70 disabled:cursor-not-allowed">
          {{ loading ? t('common.loading') : t('auth.register') }}
        </button>
      </form>
      <p class="text-center text-sm text-gray-600 mt-4">
        {{ t('auth.hasAccount') }}
        <RouterLink to="/login" class="text-secondary font-semibold hover:underline ml-1">{{ t('auth.login') }}</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const form = reactive({ prenom: '', nom: '', email: '', telephone: '', password: '', password_confirmation: '', accept: false })
const error = ref('')
const loading = ref(false)
const showPwd = ref(false)

async function handleRegister() {
  if (form.password !== form.password_confirmation) { error.value = 'Les mots de passe ne correspondent pas'; return }
  error.value = ''
  loading.value = true
  try {
    await auth.register(form)
    router.push('/dashboard/client/overview')
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Erreur lors de l\'inscription'
  } finally {
    loading.value = false
  }
}
</script>
