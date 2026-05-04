<template>
  <div ref="root" class="relative inline-block text-left">
    <button
      type="button"
      class="inline-flex items-center justify-between w-48 px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-[#2D1B08] hover:bg-gray-50 transition-all focus:outline-none"
      @click="open = !open"
      :aria-expanded="open.toString()"
      aria-haspopup="true"
    >
      <span class="flex items-center min-w-0">
        <component :is="currentIcon" class="w-4 h-4 mr-2 shrink-0 text-[#2D1B08]" />
        <span class="truncate">{{ currentLabel }}</span>
      </span>
      <ChevronDown class="w-4 h-4 text-[#2D1B08]" />
    </button>

    <transition name="fade">
      <div v-if="open" class="absolute right-0 z-50 mt-2 w-56 origin-top-right bg-white rounded-2xl shadow-xl ring-1 ring-black/5 overflow-hidden">
        <div class="py-1">
          <div
            v-for="opt in resolvedOptions"
            :key="opt.value"
            @click="onSelect(opt.value)"
            :class="itemClass(opt.value)"
            role="menuitem"
          >
            <component :is="opt.icon" class="w-4 h-4 mr-3" :class="iconClass(opt.value)" />
            <span class="font-medium">{{ opt.label }}</span>
            <Check class="w-4 h-4 ml-auto" v-if="value === opt.value" :class="checkClass" />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Sparkles, ArrowUpNarrowWide, ArrowDownWideNarrow, Star, ChevronDown, Check } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: String, default: 'recommande' },
  options: { type: Array, default: null },
})
const emit = defineEmits(['update:modelValue', 'change'])

const open = ref(false)
const value = ref(props.modelValue)

const defaultOptions = [
  { value: 'recommande', label: 'Recommandé', icon: Sparkles },
  { value: 'prix_asc', label: 'Prix croissant', icon: ArrowUpNarrowWide },
  { value: 'prix_desc', label: 'Prix décroissant', icon: ArrowDownWideNarrow },
  { value: 'note_desc', label: 'Mieux notés', icon: Star },
]

const resolvedOptions = computed(() => {
  if (Array.isArray(props.options) && props.options.length) return props.options.map(mapOption)
  return defaultOptions
})

function mapOption(o) {
  return {
    value: o.value || String(o).toString(),
    label: o.label || o.value || String(o),
    icon: typeof o.icon === 'object' || typeof o.icon === 'function'
      ? o.icon
      : (o.value === 'recommande' ? Sparkles : o.value === 'prix_asc' ? ArrowUpNarrowWide : o.value === 'prix_desc' ? ArrowDownWideNarrow : Star),
  }
}

const current = computed(() => resolvedOptions.value.find((o) => o.value === value.value) || resolvedOptions.value[0])
const currentLabel = computed(() => current.value?.label || '')
const currentIcon = computed(() => current.value?.icon || Sparkles)

function onSelect(v) {
  value.value = v
  emit('update:modelValue', v)
  emit('change', v)
  open.value = false
}

function itemClass(key) {
  return [
    'flex items-center px-4 py-3 text-sm cursor-pointer transition-colors',
    value.value === key ? 'bg-orange-50 text-[#FF8C00]' : 'text-[#2D1B08] hover:bg-orange-50 hover:text-[#FF8C00]'
  ]
}

function iconClass(key) {
  return value.value === key ? 'text-[#FF8C00]' : 'text-[#2D1B08]'
}

const checkClass = 'text-[#FF8C00]'

const root = ref(null)
function onClickOutside(e) {
  if (!root.value) return
  if (!root.value.contains(e.target)) open.value = false
}

onMounted(() => {
  document.addEventListener('click', onClickOutside)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
})

watch(() => props.modelValue, (v) => {
  value.value = v
})
</script>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.bg-cream-50 { background-color: #fffaf5; }
</style>
