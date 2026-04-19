<template>
  <div :dir="locale === 'ar' ? 'rtl' : 'ltr'">
    <button
      type="button"
      class="chatbot-fab"
      :class="{ 'chatbot-fab-open': isOpen }"
      :style="fabStyle"
      @click="toggleOpen"
      :aria-label="t('chatbot.toggle')"
    >
      <span class="chatbot-fab-ring" aria-hidden="true"></span>
      <svg viewBox="0 0 24 24" class="chatbot-fab-icon" aria-hidden="true">
        <path d="M4 5.5A3.5 3.5 0 0 1 7.5 2h9A3.5 3.5 0 0 1 20 5.5v7A3.5 3.5 0 0 1 16.5 16H10l-4.2 3.5c-.6.5-1.5 0-1.5-.8V16H7.5A3.5 3.5 0 0 1 4 12.5v-7Z" fill="currentColor"/>
        <path d="M8 7.75h8M8 11h5" stroke="#1a1f2e" stroke-width="1.7" stroke-linecap="round"/>
      </svg>
    </button>

    <transition name="chatbot-pop">
      <section v-if="isOpen" class="chatbot-window" :style="windowStyle">
        <header class="chatbot-header">
          <div class="chatbot-header-copy">
            <div class="chatbot-brand">HotelEase</div>
            <div class="chatbot-title-row">
              <span class="chatbot-online-dot" aria-hidden="true"></span>
              <span class="chatbot-online-text">{{ t('chatbot.online') }}</span>
              <span class="chatbot-title">{{ t('chatbot.title') }}</span>
            </div>
            <div class="chatbot-subtitle">{{ t('chatbot.subtitle') }}</div>
          </div>

          <button type="button" class="chatbot-close" @click="isOpen = false" :aria-label="t('chatbot.close')">×</button>
        </header>

        <div ref="messagesRef" class="chatbot-messages">
          <article
            v-for="(message, index) in messages"
            :key="`${message.role}-${index}-${message.time}`"
            class="chatbot-message-row"
            :class="message.role === 'user' ? 'chatbot-message-row-user' : 'chatbot-message-row-bot'"
          >
            <div
              class="chatbot-message-bubble"
              :class="[
                message.role === 'user' ? 'chatbot-message-user' : 'chatbot-message-bot',
                message.error ? 'chatbot-message-error' : '',
              ]"
              dir="auto"
            >
              <div class="chatbot-message-text">{{ message.text }}</div>
              <div class="chatbot-message-meta">{{ message.time }}</div>
            </div>
          </article>

          <div v-if="isTyping" class="chatbot-typing-row" aria-live="polite">
            <div class="chatbot-message-bubble chatbot-message-bot" dir="auto">
              <span class="chatbot-typing-dot"></span>
              <span class="chatbot-typing-dot"></span>
              <span class="chatbot-typing-dot"></span>
            </div>
          </div>
        </div>

        <form class="chatbot-input-row" @submit.prevent="sendMessage">
          <input
            v-model="input"
            type="text"
            class="chatbot-input"
            :placeholder="t('chatbot.placeholder')"
            :disabled="isTyping"
            @keydown.enter.prevent="sendMessage"
            dir="auto"
          />
          <button type="submit" class="chatbot-send" :disabled="isTyping || !input.trim()">
            {{ t('chatbot.send') }}
          </button>
        </form>
      </section>
    </transition>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import api from '../api'

const { t, locale } = useI18n()
const auth = useAuthStore()

const isOpen = ref(false)
const isTyping = ref(false)
const input = ref('')
const messages = ref([])
const messagesRef = ref(null)

const fabStyle = computed(() => (locale.value === 'ar'
  ? { left: '24px', right: 'auto' }
  : { right: '24px', left: 'auto' }))

const windowStyle = computed(() => (locale.value === 'ar'
  ? { left: '24px', right: 'auto' }
  : { right: '24px', left: 'auto' }))

function nowTime() {
  return new Intl.DateTimeFormat(locale.value === 'ar' ? 'ar' : 'fr', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date())
}

function scrollToBottom() {
  nextTick(() => {
    const el = messagesRef.value
    if (el) {
      el.scrollTop = el.scrollHeight
    }
  })
}

function pushGreetingIfNeeded() {
  if (messages.value.length > 0) return

  const firstName = String(auth.user?.prenom || '').trim()
  const greeting = firstName
    ? t('chatbot.greetingWithName', { name: firstName })
    : t('chatbot.greeting')

  messages.value.push({ role: 'bot', text: greeting, time: nowTime() })
  scrollToBottom()
}

function toggleOpen() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    pushGreetingIfNeeded()
    scrollToBottom()
  }
}

async function sendMessage() {
  const userMsg = input.value.trim()
  if (!userMsg || isTyping.value) return

  const history = messages.value
    .filter((m) => (m.role === 'user' || m.role === 'bot') && !m.error)
    .slice(-8)
    .map((m) => ({
      role: m.role === 'bot' ? 'assistant' : 'user',
      text: String(m.text || ''),
    }))

  messages.value.push({ role: 'user', text: userMsg, time: nowTime() })
  input.value = ''
  isTyping.value = true
  scrollToBottom()

  try {
    const { data } = await api.post('/client/chatbot', {
      message: userMsg,
      history,
    })
    messages.value.push({ role: 'bot', text: data?.reply || t('chatbot.fallbackReply'), time: nowTime() })
  } catch (error) {
    const status = Number(error?.response?.status || 0)
    const serverReply = String(error?.response?.data?.reply || '').trim()

    let text = t('chatbot.unavailable')
    if (serverReply) {
      text = serverReply
    } else if (status === 401) {
      text = t('chatbot.sessionExpired')
    } else if (!status) {
      text = t('chatbot.networkError')
    }

    messages.value.push({ role: 'bot', text, time: nowTime(), error: true })
  } finally {
    isTyping.value = false
    scrollToBottom()
  }
}

watch(isOpen, (next) => {
  if (next) {
    pushGreetingIfNeeded()
  }
})
</script>

<style scoped>
.chatbot-fab {
  position: fixed;
  bottom: 24px;
  z-index: 70;
  width: 56px;
  height: 56px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  color: #fff;
  box-shadow: 0 20px 40px rgba(245, 158, 11, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.14);
}

.chatbot-fab-ring {
  position: absolute;
  inset: -6px;
  border-radius: inherit;
  border: 1px solid rgba(245, 158, 11, 0.45);
  animation: chatbot-ring 2.2s infinite;
}

.chatbot-fab-open .chatbot-fab-ring {
  opacity: 0;
}

.chatbot-fab-icon {
  width: 26px;
  height: 26px;
  position: relative;
  z-index: 1;
}

.chatbot-window {
  position: fixed;
  bottom: 88px;
  width: 380px;
  height: 500px;
  background: #1a1f2e;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
  overflow: hidden;
  z-index: 70;
  display: flex;
  flex-direction: column;
}

.chatbot-header {
  padding: 16px 16px 14px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0));
}

.chatbot-brand {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #fbbf24;
  margin-bottom: 4px;
}

.chatbot-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.chatbot-online-dot {
  width: 9px;
  height: 9px;
  border-radius: 999px;
  background: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.16);
}

.chatbot-online-text {
  color: #86efac;
  font-size: 12px;
  font-weight: 700;
}

.chatbot-title {
  color: #fff;
  font-weight: 700;
  font-size: 16px;
}

.chatbot-subtitle {
  color: #94a3b8;
  font-size: 12px;
  margin-top: 2px;
}

.chatbot-close {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  color: #fff;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  font-size: 20px;
  line-height: 1;
}

.chatbot-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.chatbot-message-row {
  display: flex;
}

.chatbot-message-row-bot {
  justify-content: flex-start;
}

.chatbot-message-row-user {
  justify-content: flex-end;
}

.chatbot-message-bubble {
  max-width: 82%;
  padding: 10px 12px;
  border-radius: 14px;
  color: #fff;
  font-size: 13px;
  line-height: 1.45;
  word-break: break-word;
}

.chatbot-message-bot {
  background: rgba(255, 255, 255, 0.08);
  border-top-left-radius: 6px;
}

.chatbot-message-user {
  background: #f59e0b;
  border-top-right-radius: 6px;
}

.chatbot-message-error {
  background: rgba(239, 68, 68, 0.16);
  color: #fecaca;
  border: 1px solid rgba(239, 68, 68, 0.25);
}

.chatbot-message-text {
  white-space: pre-wrap;
}

.chatbot-message-meta {
  margin-top: 6px;
  font-size: 11px;
  opacity: 0.7;
}

.chatbot-typing-row {
  display: flex;
  justify-content: flex-start;
}

.chatbot-typing-dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: #cbd5e1;
  display: inline-block;
  margin-right: 5px;
  animation: chatbot-bounce 1s infinite ease-in-out;
}

.chatbot-typing-dot:nth-child(2) {
  animation-delay: 0.12s;
}

.chatbot-typing-dot:nth-child(3) {
  animation-delay: 0.24s;
}

.chatbot-input-row {
  padding: 14px;
  display: flex;
  gap: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(15, 23, 42, 0.88);
}

.chatbot-input {
  flex: 1;
  min-width: 0;
  height: 42px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255,255,255,0.04);
  color: #fff;
  padding: 0 14px;
  outline: none;
}

.chatbot-input::placeholder {
  color: #94a3b8;
}

.chatbot-send {
  height: 42px;
  min-width: 78px;
  border-radius: 12px;
  background: #f59e0b;
  color: #fff;
  font-weight: 700;
  padding: 0 16px;
}

.chatbot-send:disabled,
.chatbot-close:disabled {
  opacity: 0.55;
}

.chatbot-pop-enter-active,
.chatbot-pop-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.chatbot-pop-enter-from,
.chatbot-pop-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}

@keyframes chatbot-bounce {
  0%, 80%, 100% { transform: translateY(0); opacity: 0.5; }
  40% { transform: translateY(-4px); opacity: 1; }
}

@keyframes chatbot-ring {
  0% { transform: scale(0.85); opacity: 0.7; }
  70% { transform: scale(1.15); opacity: 0; }
  100% { opacity: 0; }
}

@media (max-width: 520px) {
  .chatbot-window {
    width: calc(100vw - 24px);
    height: 70vh;
    bottom: 84px;
  }
}
</style>