<template>
  <div class="profile-page max-w-2xl mx-auto">
    <h2 class="profile-title">{{ t('profile.title') }}</h2>
    <div class="card profile-card" dir="auto">
      <div class="flex items-center gap-4 mb-6">
        <div class="profile-avatar">
          {{ auth.user?.prenom?.[0] || 'U' }}
        </div>
        <div>
          <h3 class="profile-name">{{ auth.user?.prenom }} {{ auth.user?.nom }}</h3>
          <p class="profile-email">{{ auth.user?.email }}</p>
          <span class="profile-badge">{{ t('profile.clientBadge') }}</span>
        </div>
      </div>
      <form @submit.prevent="save" class="space-y-4">
        <p v-if="message" :class="['profile-message', message.type === 'success' ? 'profile-message-success' : 'profile-message-error']">
          {{ message.text }}
        </p>
        <div class="profile-grid-two gap-4">
          <div>
            <label class="profile-label">{{ t('profile.firstName') }}</label>
            <input v-model="form.prenom" class="input-field" />
          </div>
          <div>
            <label class="profile-label">{{ t('profile.lastName') }}</label>
            <input v-model="form.nom" class="input-field" />
          </div>
        </div>
        <div>
          <label class="profile-label">{{ t('profile.email') }}</label>
          <input v-model="form.email" type="email" class="input-field ltr-value" readonly />
        </div>
        <div>
          <label class="profile-label">{{ t('profile.phone') }}</label>
          <input v-model="form.telephone" class="input-field ltr-value" />
        </div>
        <div>
          <label class="profile-label">{{ t('profile.preferredLanguage') }}</label>
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
        <button type="submit" class="profile-save-btn w-full">{{ t('profile.saveButton') }}</button>
      </form>

      <!-- RGPD Reset Preferences Section -->
      <div class="mt-10 pt-6 border-t border-[rgba(180,110,30,0.16)]">
        <h3 class="text-lg font-bold text-[#3A1A04] mb-2">Confidentialité & Données</h3>
        <p class="text-sm text-[#7a5b38] mb-4">
          Vous pouvez réinitialiser votre historique de navigation. Cela effacera vos recommandations personnalisées.
        </p>
        
        <button 
          type="button"
          @click="handleResetPreferences" 
          :disabled="isResetting"
          class="flex items-center justify-center px-4 py-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-colors w-full sm:w-auto font-bold border border-red-100"
        >
          <Trash2 :size="18" class="mr-2" />
          <span>{{ isResetting ? 'Effacement en cours...' : 'Réinitialiser mes préférences' }}</span>
        </button>
      </div>
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
import { Trash2 } from 'lucide-vue-next'
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

const isResetting = ref(false)

const handleResetPreferences = async () => {
  if (!confirm("Êtes-vous sûr de vouloir réinitialiser votre algorithme de recommandation ?")) return;

  isResetting.value = true;
  try {
    const response = await api.post('/client/reset-preferences');
    alert(response.data.message);
    window.location.reload(); 
  } catch (error) {
    console.error("Erreur lors de la réinitialisation", error);
    alert("Une erreur est survenue lors de la réinitialisation.");
  } finally {
    isResetting.value = false;
  }
};
</script>

<style scoped>
.profile-card {
  overflow: visible;
  border: 1px solid rgba(180,110,30,0.16);
  background: rgba(255,252,245,0.96);
  box-shadow: 0 18px 48px rgba(58,26,4,0.08);
  border-radius: 20px;
}

.profile-page {
  color: var(--text-primary);
}

.profile-title {
  margin-bottom: 1.25rem;
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 800;
  color: #3A1A04;
}

.profile-avatar {
  width: 5rem;
  height: 5rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: linear-gradient(135deg, #0f6fc4, #1e88e5);
  color: #fff;
  font-size: 2rem;
  font-weight: 800;
  text-transform: lowercase;
  box-shadow: 0 10px 24px rgba(15,111,196,0.2);
}

.profile-name {
  font-family: 'Playfair Display', serif;
  font-size: 1.65rem;
  font-weight: 800;
  color: #3A1A04;
}

.profile-email {
  color: var(--text-soft);
  margin-top: 0.2rem;
}

.profile-badge {
  display: inline-flex;
  align-items: center;
  margin-top: 0.45rem;
  padding: 0.26rem 0.65rem;
  border-radius: 999px;
  background: linear-gradient(135deg, #0f6fc4, #1e88e5);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  box-shadow: 0 8px 18px rgba(15,111,196,0.18);
}

.profile-label {
  display: block;
  margin-bottom: 0.35rem;
  font-size: 0.92rem;
  font-weight: 700;
  color: #7a5b38;
}

.profile-message {
  font-size: 0.9rem;
  font-weight: 600;
}

.profile-message-success {
  color: #0f8a55;
}

.profile-message-error {
  color: #b91c1c;
}

.profile-grid-two {
  display: grid;
  grid-template-columns: 1fr 1fr;
}

.lang-dropdown {
  position: relative;
}

.lang-trigger {
  background: rgba(255,255,255,0.96);
  border: 1px solid rgba(180,110,30,0.16);
  color: #3A1A04;
  padding: 0.85rem 1rem;
  border-radius: 12px;
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
  background: #fffdfa;
  border: 1px solid rgba(180,110,30,0.16);
  border-radius: 12px;
  margin-top: 4px;
  overflow: hidden;
  box-shadow: 0 14px 34px rgba(58,26,4,0.08);
}

.lang-option {
  padding: 10px 14px;
  cursor: pointer;
  color: #3A1A04;
}

.lang-option:hover {
  background: rgba(212,130,10,0.08);
}

.lang-option-active {
  background: rgba(212,130,10,0.12);
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
  color: #8B4513;
  cursor: pointer;
}

.password-toggle-icon {
  width: 18px;
  height: 18px;
}

.dashboard-password-input {
  background: rgba(255, 255, 255, 0.96) !important;
  color: #3A1A04 !important;
  -webkit-text-fill-color: #3A1A04 !important;
  box-shadow: 0 0 0px 1000px #fffdfa inset;
  -webkit-box-shadow: 0 0 0px 1000px #fffdfa inset;
  border: 1px solid rgba(180,110,30,0.16);
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
  box-shadow: 0 0 0px 1000px #fffdfa inset !important;
  -webkit-box-shadow: 0 0 0px 1000px #fffdfa inset !important;
  -webkit-text-fill-color: #3A1A04 !important;
  caret-color: #3A1A04;
}

.profile-save-btn {
  width: 100%;
  border: 1px solid rgba(180,110,30,0.14);
  background: linear-gradient(180deg, #fffaf1 0%, #f7ead6 100%);
  color: #3A1A04;
  box-shadow: 0 10px 22px rgba(58,26,4,0.06);
  border-radius: 14px;
  padding: 0.9rem 1rem;
  font-weight: 800;
}

.profile-save-btn:hover {
  background: linear-gradient(180deg, #fff7e8 0%, #f4e0bf 100%);
}

@media (max-width: 640px) {
  .profile-grid-two {
    grid-template-columns: 1fr;
  }

  .profile-title {
    font-size: 1.7rem;
  }

  .profile-card {
    padding: 1rem;
  }
}
</style>
