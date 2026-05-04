<template>
  <section class="send-offers-page">
    <header class="page-header">
      <div>
        <h2 class="page-title">Envoyer des offres email</h2>
        <p class="page-subtitle">
          Envoyez une campagne promotionnelle aux clients actifs.
        </p>
      </div>
    </header>

    <article class="premium-card form-card">
      <form class="offer-form" @submit.prevent="submitOffer">
        <div class="field">
          <label for="subject" class="label">Sujet</label>
          <input
            id="subject"
            v-model.trim="form.subject"
            type="text"
            class="input"
            maxlength="120"
            placeholder="Ex: Offre spéciale week-end"
            required
            :disabled="loading"
          />
          <p class="hint">{{ form.subject.length }}/120</p>
        </div>

        <div class="field">
          <label for="message" class="label">Message</label>
          <textarea
            id="message"
            v-model.trim="form.message"
            class="textarea"
            rows="6"
            maxlength="5000"
            placeholder="Rédigez votre message marketing..."
            required
            :disabled="loading"
          ></textarea>
          <p class="hint">{{ form.message.length }}/5000</p>
        </div>

        <div class="field">
          <label for="discountCode" class="label">Code Promo (optionnel)</label>
          <input
            id="discountCode"
            v-model.trim="form.discountCode"
            type="text"
            class="input"
            maxlength="50"
            placeholder="Ex: SUMMER25"
            :disabled="loading"
          />
        </div>

        <div v-if="errorMessage" class="alert alert-error">
          {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success">
          {{ successMessage }}
        </div>

        <div class="actions">
          <button type="submit" class="btn-submit" :disabled="loading || !canSubmit">
            {{ loading ? 'Envoi en cours...' : 'Envoyer l\'offre' }}
          </button>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import axios from 'axios'

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = reactive({
  subject: '',
  message: '',
  discountCode: '',
})

const canSubmit = computed(() => form.subject.trim() !== '' && form.message.trim() !== '')

function resetMessages() {
  errorMessage.value = ''
  successMessage.value = ''
}

function resetForm() {
  form.subject = ''
  form.message = ''
  form.discountCode = ''
}

async function submitOffer() {
  resetMessages()

  if (!canSubmit.value) {
    errorMessage.value = 'Le sujet et le message sont obligatoires.'
    return
  }

  try {
    loading.value = true

    const token = localStorage.getItem('token')
    if (!token) {
      errorMessage.value = 'Session expirée. Veuillez vous reconnecter.'
      return
    }

    const payload = {
      subject: form.subject,
      message: form.message,
      discountCode: form.discountCode || null,
    }

    const { data } = await axios.post('/api/marketing/offers/email', payload, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    })

    const sent = Number(data?.sent || 0)
    successMessage.value = sent > 0
      ? `Offre envoyée avec succès à ${sent} client(s).`
      : 'Offre envoyée avec succès.'

    resetForm()
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      error?.response?.data?.error ||
      'Impossible d\'envoyer les offres pour le moment.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.send-offers-page {
  --bg-card: #FFFFFF;
  --text-main: #3A1A04;
  --text-soft: #A07040;
  --accent: #D4820A;
  --border: rgba(180,110,30,0.12);
  --danger-bg: rgba(239, 68, 68, 0.08);
  --danger-text: #fca5a5;
  --success-bg: rgba(16, 185, 129, 0.08);
  --success-text: #34d399;
}

.page-header {
  margin-bottom: 1rem;
}

.page-title {
  color: var(--text-main);
  font-size: 1.6rem;
  font-weight: 800;
}

.page-subtitle {
  color: var(--text-soft);
  margin-top: 0.35rem;
  font-size: 0.92rem;
}

.premium-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 18px;
  color: var(--text-main);
  transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.premium-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(58,26,4,0.06);
}

.offer-form {
  display: grid;
  gap: 1rem;
}

.field {
  display: grid;
  gap: 0.45rem;
}

.label {
  color: var(--text-soft);
  font-size: 0.88rem;
  font-weight: 700;
}

.input,
.textarea {
  width: 100%;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,0.95);
  color: var(--text-primary);
  padding: 0.6rem 0.7rem;
  outline: none;
}

.input:focus,
.textarea:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(212,130,10,0.12);
}

.input:disabled,
.textarea:disabled {
  opacity: 0.65;
}

.textarea {
  resize: vertical;
  min-height: 140px;
}

.hint {
  color: var(--text-soft);
  font-size: 0.75rem;
  text-align: end;
}

.alert {
  border-radius: 10px;
  padding: 0.7rem 0.85rem;
  font-size: 0.84rem;
  font-weight: 600;
}

.alert-error {
  background: var(--danger-bg);
  color: var(--danger-text);
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.alert-success {
  background: var(--success-bg);
  color: var(--success-text);
  border: 1px solid rgba(16, 185, 129, 0.32);
}

.actions {
  display: flex;
  justify-content: flex-end;
}

.btn-submit {
  background: linear-gradient(135deg, #FDF3DC, #FCECCF);
  color: var(--accent);
  border: 1px solid rgba(212,130,10,0.12);
  border-radius: 10px;
  padding: 0.62rem 1rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
