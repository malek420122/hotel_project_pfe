<template>
  <div class="min-h-screen flex" style="background: linear-gradient(135deg, #003580 0%, #0071c2 100%);">
    <div class="hidden lg:flex lg:w-1/2 items-center justify-center p-12 text-white">
      <div>
        <RouterLink to="/" class="text-4xl font-extrabold flex items-center gap-3 mb-8">🏨 HotelEase</RouterLink>
        <h2 class="text-3xl font-bold mb-4">Bienvenue à nouveau !</h2>
        <p class="text-white/70 text-lg mb-8">Connectez-vous pour accéder à vos réservations et profiter de toutes nos fonctionnalités.</p>
        <div class="grid grid-cols-2 gap-4">
          <div v-for="feat in features" :key="feat" class="bg-white/10 rounded-xl p-4 text-sm">{{ feat }}</div>
        </div>
      </div>
    </div>
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
      <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-6">
          <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3">🏨</div>
          <h1 class="text-2xl font-extrabold text-gray-800">{{ t('auth.loginTitle') }}</h1>
        </div>
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-xl p-3 text-red-700 text-sm mb-4">{{ error }}</div>
        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('auth.email') }}</label>
            <input v-model="form.email" type="email" :placeholder="t('auth.email')" class="input-field" required />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('auth.password') }}</label>
            <div class="relative">
              <input v-model="form.password" :type="showPwd ? 'text' : 'password'" :placeholder="t('auth.password')" class="input-field pr-10" required />
              <button type="button" @click="showPwd = !showPwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                {{ showPwd ? '🙈' : '👁️' }}
              </button>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="form.remember" class="rounded" />
              <span class="text-sm text-gray-600">Se souvenir de moi</span>
            </label>
            <a href="#" class="text-sm text-secondary hover:underline">{{ t('auth.forgotPassword') }}</a>
          </div>
          <button type="submit" :disabled="loading"
            class="btn-primary w-full py-3 text-base disabled:opacity-70 disabled:cursor-not-allowed">
            {{ loading ? t('common.loading') : t('auth.login') }}
          </button>
        </form>
        <p class="text-center text-sm text-gray-600 mt-5">
          {{ t('auth.noAccount') }}
          <RouterLink to="/register" class="text-secondary font-semibold hover:underline ml-1">{{ t('auth.register') }}</RouterLink>
        </p>
        <div class="mt-4 pt-4 border-t">
          <LanguageSwitcher />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const form = reactive({ email: '', password: '', remember: false })
const error = ref('')
const loading = ref(false)
const showPwd = ref(false)

const features = ['✅ Réservations simplifiées', '🔔 Notifications en temps réel', '💳 Paiements sécurisés', '🎁 Programme fidélité']

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    const user = await auth.login(form)
    const roleMap = { client: '/dashboard/client', admin: '/dashboard/admin', receptionniste: '/dashboard/receptionniste', marketing: '/dashboard/marketing' }
    router.push(roleMap[user.role] || '/')
  } catch (e) {
    if (e.response?.status === 429) error.value = t('auth.accountBlocked')
    else error.value = e.response?.data?.message || 'Email ou mot de passe incorrect'
  } finally {
    loading.value = false
  }
}
</script>
