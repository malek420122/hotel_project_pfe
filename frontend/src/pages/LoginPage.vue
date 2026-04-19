<template>
  <div class="auth-page min-h-screen">
    <div class="auth-overlay min-h-screen flex">
      <aside class="hidden lg:flex lg:w-1/2 items-center justify-center p-14 text-white">
        <div class="max-w-xl">
          <RouterLink to="/" class="brand-logo inline-flex items-center mb-8">
            <AppLogo variant="dark" size="lg" />
          </RouterLink>
          <h2 class="font-display text-5xl leading-tight mb-5">{{ t('auth.welcomeBack') }}</h2>
          <p class="text-white/85 text-lg mb-8">{{ t('home.footerDescription') }}</p>
          <div class="space-y-3">
            <div class="feature-badge" style="--delay: 0.1s">
              <span>✨</span>
              <span>{{ t('auth.feature1') }}</span>
            </div>
            <div class="feature-badge" style="--delay: 0.25s">
              <span>🛎️</span>
              <span>{{ t('auth.feature2') }}</span>
            </div>
            <div class="feature-badge" style="--delay: 0.4s">
              <span>🔐</span>
              <span>{{ t('auth.feature3') }}</span>
            </div>
          </div>
        </div>
      </aside>

      <main class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8">
        <div class="glass-card w-full max-w-md p-6 sm:p-8">
          <div class="text-center mb-6">
            <h1 class="font-display text-4xl text-white mb-2">{{ t('auth.loginTitle') }}</h1>
            <p class="text-white/75 text-sm">{{ t('auth.loginSubtitle') }}</p>
          </div>

          <div v-if="error" :class="['error-box', errorShake ? 'shake' : '']">{{ error }}</div>

          <form @submit.prevent="handleLogin" class="space-y-4">
            <div class="field-row" style="--d: 0.05s">
              <label class="auth-label">{{ t('auth.email') }}</label>
              <input v-model="form.email" type="email" :placeholder="t('auth.email')" class="auth-input" required />
            </div>

            <div class="field-row" style="--d: 0.12s">
              <label class="auth-label">{{ t('auth.password') }}</label>
              <div class="password-field-wrapper">
                <input
                  v-model="form.password"
                  :type="showPwd ? 'text' : 'password'"
                  :placeholder="t('auth.password')"
                  :class="['auth-input', 'password-input', showPwd ? 'password-visible' : '']"
                  required
                />
                <button
                  type="button"
                  @click="showPwd = !showPwd"
                  class="password-toggle"
                  :aria-label="showPwd ? t('auth.hidePassword') : t('auth.showPassword')"
                >
                  <svg v-if="!showPwd" xmlns="http://www.w3.org/2000/svg" class="password-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="password-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17.94 17.94A10.9 10.9 0 0 1 12 20C5 20 1 12 1 12a21.78 21.78 0 0 1 5.06-7.94" />
                    <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-3.17 5.19" />
                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                    <line x1="1" y1="1" x2="23" y2="23" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="field-row" style="--d: 0.2s">
              <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 cursor-pointer text-white/85">
                  <input type="checkbox" v-model="form.remember" class="rounded" />
                  <span>{{ t('auth.rememberMe') }}</span>
                </label>
                <a href="#" class="text-cyan-200 hover:text-white">{{ t('auth.forgotPassword') }}</a>
              </div>
            </div>

            <button type="submit" :disabled="loading" :class="['cta-btn', successState ? 'success' : '']">
              <span v-if="successState" class="inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="20 6 9 17 4 12" />
                </svg>
                {{ t('common.success') }}
              </span>
              <span v-else>{{ loading ? t('common.loading') : t('auth.login') }}</span>
            </button>
          </form>

          <div class="mt-5 pt-4 border-t border-white/20">
            <LanguageSwitcher />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import AppLogo from '../components/AppLogo.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const form = reactive({ email: '', password: '', remember: false })
const error = ref('')
const loading = ref(false)
const showPwd = ref(false)
const successState = ref(false)
const errorShake = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  successState.value = false
  try {
    const user = await auth.login(form)
    successState.value = true
    await new Promise((resolve) => setTimeout(resolve, 550))
    const queryRedirect = typeof route.query.redirect === 'string' ? route.query.redirect : ''
    const storedRedirect = sessionStorage.getItem('postLoginRedirect') || ''
    let redirect = queryRedirect || storedRedirect

    if (redirect === '/dashboard/client/new-booking') {
      const q = new URLSearchParams()
      if (typeof route.query.hotelId === 'string') q.set('hotelId', route.query.hotelId)
      if (typeof route.query.chambreId === 'string') q.set('chambreId', route.query.chambreId)
      if (typeof route.query.dateArrivee === 'string') q.set('dateArrivee', route.query.dateArrivee)
      if (typeof route.query.dateDepart === 'string') q.set('dateDepart', route.query.dateDepart)
      if (typeof route.query.nbVoyageurs === 'string') q.set('nbVoyageurs', route.query.nbVoyageurs)
      if ([...q.keys()].length) redirect = `${redirect}?${q.toString()}`
    }

    if (redirect && redirect.startsWith('/')) {
      sessionStorage.removeItem('postLoginRedirect')
      router.push(redirect)
      return
    }

    const roleMap = { client: '/dashboard/client', admin: '/dashboard/admin', receptionniste: '/dashboard/receptionniste', marketing: '/dashboard/marketing' }
    router.push(roleMap[user.role] || '/')
  } catch (e) {
    if (e.response?.status === 429) error.value = t('auth.accountBlocked')
    else error.value = e.response?.data?.message || t('auth.invalidCredentials')
    errorShake.value = false
    requestAnimationFrame(() => {
      errorShake.value = true
      setTimeout(() => { errorShake.value = false }, 450)
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  showPwd.value = false
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap');

.auth-page {
  background-image: linear-gradient(rgba(10, 30, 80, 0.85), rgba(10, 30, 80, 0.85)), url('https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=2000&q=80');
  background-size: cover;
  background-position: center;
  font-family: 'Inter', sans-serif;
}

.auth-overlay {
  min-height: 100vh;
}

.font-display {
  font-family: 'Playfair Display', serif;
}

.brand-logo {
  animation: floatLogo 5s ease-in-out infinite;
}

.feature-badge {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.26);
  border-radius: 999px;
  padding: 0.7rem 1rem;
  animation: fadeInBadge 0.6s ease forwards;
  animation-delay: var(--delay);
  opacity: 0;
}

.glass-card {
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  box-shadow: 0 20px 60px rgba(4, 15, 45, 0.45), 0 0 35px rgba(77, 145, 255, 0.2);
  border-radius: 1.25rem;
  animation: fadeInUp 0.65s ease;
}

.field-row {
  animation: slideInField 0.55s ease both;
  animation-delay: var(--d);
}

.auth-label {
  display: block;
  color: rgba(255, 255, 255, 0.86);
  font-size: 0.78rem;
  font-weight: 700;
  margin-bottom: 0.35rem;
}

.auth-input {
  width: 100%;
  border-radius: 0.85rem;
  border: 1px solid rgba(26, 58, 107, 0.24);
  background: rgba(255, 255, 255, 0.9);
  color: #0f172a;
  padding: 0.72rem 0.92rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.auth-input::placeholder {
  color: rgba(15, 23, 42, 0.62);
}

.auth-input:focus {
  outline: none;
  border-color: #1a56db;
  box-shadow: 0 0 0 2px rgba(26, 86, 219, 0.2);
  background: rgba(255, 255, 255, 0.98);
}

.password-field-wrapper {
  position: relative;
}

.password-toggle {
  position: absolute;
  inset-inline-end: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #1a56db;
  z-index: 2;
  opacity: 1;
  transition: opacity 0.2s ease;
}

.password-toggle:hover {
  opacity: 0.7;
}

.password-toggle-icon {
  width: 20px;
  height: 20px;
}

.password-input {
  padding-inline-end: 44px;
  padding-inline-start: 16px;
}

.password-input[type="password"],
.password-input[type="text"].password-visible {
  direction: ltr;
  text-align: left;
}

:global([dir="rtl"]) .password-input[type="password"],
:global([dir="rtl"]) .password-input[type="text"].password-visible {
  text-align: right;
}

.cta-btn {
  width: 100%;
  border: none;
  color: white;
  font-weight: 700;
  border-radius: 0.9rem;
  padding: 0.82rem 1rem;
  background: linear-gradient(90deg, #1a56db, #06b6d4);
  box-shadow: 0 10px 24px rgba(26, 86, 219, 0.35);
  position: relative;
  overflow: hidden;
  transition: transform 0.2s ease;
}

.cta-btn::after {
  content: '';
  position: absolute;
  top: 0;
  left: -120%;
  width: 120%;
  height: 100%;
  background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.35), transparent);
  transition: left 0.45s ease;
}

.cta-btn:hover {
  transform: scale(1.02);
}

.cta-btn:hover::after {
  left: 120%;
}

.cta-btn.success {
  background: linear-gradient(90deg, #16a34a, #22c55e);
}

.cta-btn:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.social-divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.84rem;
  margin: 0.35rem 0;
}

.social-divider::before,
.social-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: rgba(255, 255, 255, 0.35);
}

.social-btn {
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 0.85rem;
  color: white;
  padding: 0.7rem 0.8rem;
  font-size: 0.86rem;
  font-weight: 600;
  transition: transform 0.18s ease, filter 0.18s ease;
}

.social-btn:hover {
  transform: translateY(-1px);
  filter: brightness(1.05);
}

.social-btn.google {
  background: linear-gradient(90deg, #ea4335, #fbbc05);
}

.social-btn.facebook {
  background: linear-gradient(90deg, #1877f2, #0a4dad);
}

.error-box {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(248, 113, 113, 0.55);
  border-radius: 0.8rem;
  padding: 0.65rem 0.75rem;
  color: #fee2e2;
  font-size: 0.86rem;
  margin-bottom: 1rem;
}

.shake {
  animation: shakeX 0.35s ease;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInField {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes shakeX {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-7px); }
  40% { transform: translateX(7px); }
  60% { transform: translateX(-5px); }
  80% { transform: translateX(5px); }
}

@keyframes fadeInBadge {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes floatLogo {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

@media (max-width: 767px) {
  .glass-card {
    width: 90%;
    max-width: 90%;
  }
}
</style>
