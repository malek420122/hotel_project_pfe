<template>
  <div class="max-w-xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('profile.title') }}</h2>
    <div class="card profile-card" dir="auto">
      <div class="flex items-center gap-4 mb-6">
        <div class="w-20 h-20 bg-secondary rounded-full flex items-center justify-center text-white text-3xl font-bold">
          {{ auth.user?.prenom?.[0] || 'U' }}
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-800">{{ auth.user?.prenom }} {{ auth.user?.nom }}</h3>
          <p class="text-gray-500">{{ auth.user?.email }}</p>
          <span class="text-xs bg-secondary text-white px-3 py-1 rounded-full mt-1 inline-block">{{ t('profile.clientBadge') }}</span>
        </div>
      </div>
      <form @submit.prevent="save" class="space-y-4">
        <p v-if="message" :class="['text-sm', message.type === 'success' ? 'text-green-600' : 'text-red-500']">
          {{ message.text }}
        </p>
        <div class="profile-grid-two gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('profile.firstName') }}</label>
            <input v-model="form.prenom" class="input-field" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('profile.lastName') }}</label>
            <input v-model="form.nom" class="input-field" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('profile.email') }}</label>
          <input v-model="form.email" type="email" class="input-field ltr-value" readonly />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('profile.phone') }}</label>
          <input v-model="form.telephone" class="input-field ltr-value" />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('profile.preferredLanguage') }}</label>
          <div class="lang-dropdown">
            <div class="lang-trigger" dir="auto" @click="langOpen = !langOpen">
              <span>{{ selectedLangLabel }}</span>
              <span>▾</span>
            </div>
            <div v-show="langOpen" class="lang-menu">
              <div
                v-for="lang in languages"
                :key="lang.value"
                class="lang-option"
                :class="{ 'lang-option-active': form.langue === lang.value }"
                @click="setLanguage(lang.value)"
              >
                {{ lang.label }}
              </div>
            </div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('profile.oldPasswordLabel') }}</label>
          <div class="password-field-wrapper">
            <input
              v-model="form.oldPassword"
              :type="showOldPassword ? 'text' : 'password'"
              class="input-field dashboard-password-input ltr-value"
              :placeholder="t('profile.oldPasswordPlaceholder')"
              autocomplete="current-password"
            />
            <button
              type="button"
              class="password-toggle"
              @click.prevent="showOldPassword = !showOldPassword"
              aria-label="Toggle old password visibility"
            >
              <svg v-if="!showOldPassword" xmlns="http://www.w3.org/2000/svg" class="password-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('profile.newPasswordLabel') }}</label>
          <div class="password-field-wrapper">
            <input
              v-model="form.password"
              :type="showNewPassword ? 'text' : 'password'"
              class="input-field dashboard-password-input ltr-value"
              :placeholder="t('profile.newPasswordPlaceholder')"
              autocomplete="new-password"
            />
            <button
              type="button"
              class="password-toggle"
              @click.prevent="showNewPassword = !showNewPassword"
              aria-label="Toggle new password visibility"
            >
              <svg v-if="!showNewPassword" xmlns="http://www.w3.org/2000/svg" class="password-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('profile.confirmPasswordLabel') }}</label>
          <div class="password-field-wrapper">
            <input
              v-model="form.passwordConfirmation"
              :type="showConfirmPassword ? 'text' : 'password'"
              class="input-field dashboard-password-input ltr-value"
              :placeholder="t('profile.confirmPasswordPlaceholder')"
              autocomplete="new-password"
            />
            <button
              type="button"
              class="password-toggle"
              @click.prevent="showConfirmPassword = !showConfirmPassword"
              aria-label="Toggle confirm password visibility"
            >
              <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="password-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
        <button type="submit" class="btn-primary w-full">{{ t('profile.saveButton') }}</button>
      </form>
    </div>
  </div>
</template>
<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../../stores/auth'
import { usePasswordToggle } from '../../../composables/usePasswordToggle'
import api from '../../../api'
import { changeLanguage } from '../../../i18n'
const auth = useAuthStore()
const { locale, t } = useI18n()
const message = ref(null)
const langOpen = ref(false)
const { showOldPassword, showNewPassword, showConfirmPassword } = usePasswordToggle()
const languages = [
  { value: 'fr', label: 'Français' },
  { value: 'ar', label: 'العربية' },
  { value: 'en', label: 'English' },
]
const form = reactive({
  prenom: auth.user?.prenom || '',
  nom: auth.user?.nom || '',
  email: auth.user?.email || '',
  telephone: auth.user?.telephone || '',
  langue: changeLanguage(auth.user?.langue || locale.value || 'fr'),
  oldPassword: '',
  password: '',
  passwordConfirmation: '',
})

const selectedLangLabel = computed(() =>
  languages.find((l) => l.value === form.langue)?.label ?? 'Français'
)

const setLanguage = (selectedLanguage) => {
  const normalized = changeLanguage(selectedLanguage)
  form.langue = normalized
  locale.value = normalized
  langOpen.value = false
}

const onDocClick = (e) => {
  if (!e.target.closest('.lang-dropdown')) {
    langOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
})

async function save() {
  try {
    if (form.password && form.password !== form.passwordConfirmation) {
      message.value = { type: 'error', text: t('profile.passwordMismatch') }
      return
    }
    message.value = null
    const payload = {
      prenom: form.prenom,
      nom: form.nom,
      telephone: form.telephone,
      langue: form.langue,
    }
    if (form.password) {
      payload.old_password = form.oldPassword
      payload.password = form.password
      payload.password_confirmation = form.passwordConfirmation
    }
    const { data } = await api.put('/profile', payload)
    auth.updateUser(data)
    message.value = { type: 'success', text: t('profile.updateSuccess') }
    form.oldPassword = ''
    form.password = ''
    form.passwordConfirmation = ''
  } catch (e) {
    message.value = { type: 'error', text: e.response?.data?.message || t('profile.updateError') }
  }
}
</script>

<style scoped>
.profile-card {
  overflow: visible;
}

.profile-grid-two {
  display: grid;
  grid-template-columns: 1fr 1fr;
}

.lang-dropdown {
  position: relative;
}

.lang-trigger {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: white;
  padding: 10px 14px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.lang-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 50;
  background: #1e2535;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  margin-top: 4px;
  overflow: hidden;
}

.lang-option {
  padding: 10px 14px;
  cursor: pointer;
  color: white;
}

.lang-option:hover {
  background: rgba(255, 255, 255, 0.08);
}

.lang-option-active {
  background: rgba(255, 255, 255, 0.15);
}

.password-field-wrapper {
  position: relative;
}

.password-toggle {
  position: absolute;
  inset-inline-end: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  padding: 0;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
}

.password-toggle-icon {
  width: 18px;
  height: 18px;
}

.dashboard-password-input {
  background: rgba(255, 255, 255, 0.08) !important;
  color: white !important;
  -webkit-text-fill-color: white !important;
  box-shadow: 0 0 0px 1000px #1a2035 inset;
  -webkit-box-shadow: 0 0 0px 1000px #1a2035 inset;
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding-inline-end: 42px;
}

.ltr-value {
  direction: ltr;
  text-align: left;
}

input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  box-shadow: 0 0 0px 1000px #13192b inset !important;
  -webkit-box-shadow: 0 0 0px 1000px #13192b inset !important;
  -webkit-text-fill-color: white !important;
  caret-color: white;
}
</style>
