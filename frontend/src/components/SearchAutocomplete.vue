<template>
  <div ref="rootRef" :class="['relative overflow-visible', wrapperClass]">
    <input
      ref="inputRef"
      :value="modelValue"
      :type="inputType"
      :placeholder="placeholder"
      :class="inputClass"
      @input="onInput"
      @focus="onFocus"
      @keydown.esc="hideSuggestions"
      @keyup.enter="onEnter"
    />

    <Teleport to="body" :disabled="!isFixedOverlayMode">
      <transition name="dropdown-fade-slide">
        <div
          v-if="showSuggestions"
          :class="dropdownClass"
          :style="dropdownStyle"
        >
          <div v-if="suggestionsLoading" class="dropdown-loading">{{ t('common.loadingSuggestions') }}</div>
          <button
            v-for="(item, idx) in suggestions"
            :key="`${item.type}-${item.value}-${idx}`"
            type="button"
            class="dropdown-item"
            @mousedown.prevent="selectSuggestion(item)"
          >
            <span class="dropdown-item-main">
              <span class="dropdown-icon" aria-hidden="true">{{ item.type === 'hotel' ? '🏨' : '📍' }}</span>
              <span class="dropdown-value">{{ displayValue(item) }}</span>
            </span>
            <span
              v-if="item.type !== 'hotel'"
              class="dropdown-badge dropdown-badge-city"
            >
              {{ t('searchbar.cityBadge') }}
            </span>
            <span
              v-else
              class="dropdown-badge dropdown-badge-hotel"
            >
              {{ t('searchbar.hotelBadge') }}
            </span>
          </button>
          <div v-if="!suggestionsLoading && !suggestions.length" class="dropdown-empty">
            <span aria-hidden="true">🔍</span>
            <span>{{ t('common.noResults') }}</span>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../api'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  wrapperClass: { type: String, default: 'relative flex-1' },
  inputClass: { type: String, default: 'input-field w-full bg-white' },
  dropdownClass: {
    type: String,
    default: 'autocomplete-dropdown',
  },
  debounceMs: { type: Number, default: 300 },
  inputType: { type: String, default: 'text' },
  dropdownWidthMode: {
    type: String,
    default: 'self',
    validator: (value) => ['self', 'full-search-bar'].includes(value),
  },
  dropdownContainerSelector: { type: String, default: '' },
  dropdownZIndex: { type: Number, default: 999999 },
  dropdownMaxHeight: { type: Number, default: 350 },
})

const emit = defineEmits(['update:modelValue', 'select', 'enter'])
const { t } = useI18n()

const rootRef = ref(null)
const inputRef = ref(null)
const suggestions = ref([])
const suggestionsLoading = ref(false)
const showSuggestions = ref(false)
const skipNextSuggestionFetch = ref(false)
const dropdownStyle = ref({})
let debounceTimer = null

const isFixedOverlayMode = computed(() => props.dropdownWidthMode === 'full-search-bar')

function getContainerElement() {
  if (!props.dropdownContainerSelector) return null
  return document.querySelector(props.dropdownContainerSelector)
}

function updateDropdownPosition() {
  if (!isFixedOverlayMode.value || !showSuggestions.value) {
    dropdownStyle.value = {
      zIndex: String(props.dropdownZIndex),
      maxHeight: `${props.dropdownMaxHeight}px`,
      overflowY: 'auto',
    }
    return
  }

  const inputEl = inputRef.value
  const containerEl = getContainerElement()
  if (!inputEl) return

  const inputRect = inputEl.getBoundingClientRect()
  const containerRect = containerEl ? containerEl.getBoundingClientRect() : inputRect

  dropdownStyle.value = {
    position: 'fixed',
    left: `${containerRect.left}px`,
    top: `${inputRect.bottom + 8}px`,
    width: `${containerRect.width}px`,
    zIndex: String(props.dropdownZIndex),
    maxHeight: `${props.dropdownMaxHeight}px`,
    overflowY: 'auto',
  }
}

function hideSuggestions() {
  showSuggestions.value = false
}

function onInput(event) {
  emit('update:modelValue', event?.target?.value ?? '')
}

function onFocus() {
  if (suggestions.value.length > 0) {
    showSuggestions.value = true
    nextTick(() => {
      updateDropdownPosition()
    })
  }
}

function onEnter() {
  hideSuggestions()
  emit('enter')
}

function displayValue(item) {
  return item?.value || item?.nom || item?.ville || ''
}

async function fetchSuggestions(query) {
  const q = String(query || '').trim()
  if (!q) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }

  suggestionsLoading.value = true
  try {
    const { data } = await api.get('/hotels/suggestions', { params: { q } })
    const parsedSuggestions = Array.isArray(data)
      ? data
      : (Array.isArray(data?.data) ? data.data : [])
    suggestions.value = parsedSuggestions.slice(0, 8)
    showSuggestions.value = true
    nextTick(() => {
      updateDropdownPosition()
    })
  } catch {
    suggestions.value = []
    showSuggestions.value = true
  } finally {
    suggestionsLoading.value = false
  }
}

function selectSuggestion(item) {
  skipNextSuggestionFetch.value = true
  emit('update:modelValue', displayValue(item))
  emit('select', item)
  hideSuggestions()
}

function handleDocumentClick(event) {
  if (!rootRef.value) return
  if (!rootRef.value.contains(event.target)) {
    hideSuggestions()
  }
}

function handleViewportChange() {
  if (!showSuggestions.value) return
  updateDropdownPosition()
}

watch(
  () => props.modelValue,
  (value) => {
    if (skipNextSuggestionFetch.value) {
      skipNextSuggestionFetch.value = false
      return
    }

    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }

    debounceTimer = setTimeout(() => {
      fetchSuggestions(value)
    }, props.debounceMs)
  },
)

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
  window.addEventListener('scroll', handleViewportChange, true)
  window.addEventListener('resize', handleViewportChange)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  window.removeEventListener('scroll', handleViewportChange, true)
  window.removeEventListener('resize', handleViewportChange)
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
})
</script>

<style scoped>
.autocomplete-dropdown {
  position: absolute;
  left: 0;
  top: calc(100% + 8px);
  width: 100%;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
  z-index: 999999;
  overflow-y: auto;
  max-height: 350px;
}

.dropdown-loading {
  padding: 14px 20px;
  font-size: 16px;
  color: #475569;
  font-weight: 600;
}

.dropdown-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  text-align: left;
  padding: 14px 20px;
  font-size: 16px;
  color: #1e293b;
  background: #ffffff;
  border-left: 3px solid transparent;
  transition: background 0.2s ease, border-left-color 0.2s ease;
}

.dropdown-item:hover,
.dropdown-item:focus {
  background: #f0f7ff;
  border-left-color: #f59e0b;
}

.dropdown-item-main {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.dropdown-icon {
  flex-shrink: 0;
}

.dropdown-value {
  font-weight: 600;
  color: #1e293b;
  white-space: normal;
  word-break: break-word;
}

.dropdown-badge {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  color: #ffffff;
  padding: 3px 10px;
  font-size: 11px;
  font-weight: 700;
}

.dropdown-badge-city {
  background: #1a56db;
}

.dropdown-badge-hotel {
  background: #f59e0b;
}

.dropdown-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-align: center;
  font-style: italic;
  color: #64748b;
  padding: 18px 20px;
  font-size: 16px;
}

.dropdown-fade-slide-enter-active,
.dropdown-fade-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.dropdown-fade-slide-enter-from,
.dropdown-fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.dropdown-fade-slide-enter-to,
.dropdown-fade-slide-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>
