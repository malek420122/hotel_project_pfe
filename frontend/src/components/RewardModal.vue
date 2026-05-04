<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="isOpen" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content relative">
          <!-- Close button -->
          <button @click="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
            <X :size="24" />
          </button>

          <!-- Modal Body -->
          <div class="text-center px-6 py-8 md:px-12 md:py-10">
            <div class="mb-6 flex justify-center">
              <div class="w-20 h-20 bg-[#FFF8E7] rounded-full flex items-center justify-center border-4 border-[#D4AF37]/20 shadow-inner">
                <Gift :size="40" class="text-[#D4AF37]" />
              </div>
            </div>
            
            <h2 class="font-serif text-3xl font-bold text-[#3E2723] mb-2 tracking-tight">
              Félicitations !
            </h2>
            <p class="text-[#3E2723]/80 font-medium text-lg mb-8">
              Votre récompense est prête.
            </p>

            <div class="bg-[#FAF7F2] border border-[#E8DCC4] rounded-2xl p-6 mb-8 relative overflow-hidden group">
              <!-- Decorative elements -->
              <div class="absolute -top-6 -right-6 w-16 h-16 bg-[#D4AF37]/10 rounded-full blur-xl group-hover:bg-[#D4AF37]/20 transition-all duration-500"></div>
              <div class="absolute -bottom-6 -left-6 w-16 h-16 bg-[#D4AF37]/10 rounded-full blur-xl group-hover:bg-[#D4AF37]/20 transition-all duration-500"></div>
              
              <p class="text-xs uppercase tracking-[0.2em] font-bold text-[#8B7355] mb-3">
                Votre Code Promo Privilège
              </p>
              <div class="font-mono text-3xl md:text-4xl font-black text-[#3E2723] tracking-wider py-2">
                {{ promoCode }}
              </div>
            </div>

            <button 
              @click="copyCode" 
              class="w-full flex items-center justify-center gap-3 bg-[#3E2723] hover:bg-[#2A1A17] text-white py-4 px-6 rounded-xl font-bold text-lg transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl shadow-[#3E2723]/30"
            >
              <template v-if="copied">
                <Check :size="22" class="text-[#D4AF37]" />
                <span class="text-[#D4AF37]">Code copié !</span>
              </template>
              <template v-else>
                <Copy :size="22" />
                <span>Copier le code</span>
              </template>
            </button>

            <div class="mt-8 flex items-center justify-center gap-2 text-sm text-[#8B7355] bg-[#FAF7F2] py-3 px-4 rounded-lg border border-[#E8DCC4]/50">
              <Info :size="16" />
              <p>Ce code est valable 3 mois et vous a été envoyé par email.</p>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { X, Gift, Copy, Check, Info } from 'lucide-vue-next'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  promoCode: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:isOpen'])

const copied = ref(false)

const closeModal = () => {
  emit('update:isOpen', false)
  setTimeout(() => { copied.value = false }, 300) // reset after animation
}

const copyCode = async () => {
  if (!props.promoCode) return
  try {
    await navigator.clipboard.writeText(props.promoCode)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 3000)
  } catch (err) {
    console.error('Failed to copy text: ', err)
  }
}

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    copied.value = false
  }
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(62, 39, 35, 0.6); /* Chocolate brown tint */
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: #FFFFFF;
  width: 100%;
  max-width: 480px;
  border-radius: 24px;
  box-shadow: 
    0 25px 50px -12px rgba(62, 39, 35, 0.25),
    0 0 0 1px rgba(212, 175, 55, 0.1); /* Subtle gold border */
  border: 1px solid rgba(212, 175, 55, 0.2);
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}
</style>
