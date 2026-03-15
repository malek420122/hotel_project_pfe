<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm">
          <div :class="['w-14 h-14 rounded-full flex items-center justify-center text-3xl mx-auto mb-4', danger ? 'bg-red-100' : 'bg-blue-100']">
            {{ danger ? '⚠️' : '❓' }}
          </div>
          <h3 class="text-xl font-bold text-gray-800 text-center mb-2">{{ title }}</h3>
          <p class="text-gray-500 text-center text-sm mb-6">{{ message }}</p>
          <div class="flex gap-3">
            <button @click="$emit('cancel')" class="btn-outline flex-1">Annuler</button>
            <button @click="$emit('confirm')" :class="['flex-1 py-2.5 rounded-xl font-semibold text-white transition-colors', danger ? 'bg-red-500 hover:bg-red-600' : 'bg-secondary hover:bg-primary']">
              Confirmer
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  show: Boolean,
  title: { type: String, default: 'Confirmer l\'action' },
  message: { type: String, default: 'Êtes-vous sûr de vouloir effectuer cette action ?' },
  danger: Boolean,
})
defineEmits(['confirm', 'cancel'])
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
