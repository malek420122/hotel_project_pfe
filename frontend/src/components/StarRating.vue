<template>
  <div class="flex gap-0.5">
    <button v-for="i in 5" :key="i" type="button"
      @click="readonly ? null : $emit('update:modelValue', i)"
      @mouseover="readonly ? null : hovered = i"
      @mouseleave="hovered = 0"
      :class="['text-xl transition-colors', readonly ? 'cursor-default' : 'cursor-pointer hover:scale-110 transition-transform']">
      <span :class="i <= (hovered || modelValue) ? 'text-accent' : 'text-gray-200'">★</span>
    </button>
    <span v-if="showValue" class="text-sm text-gray-500 ml-1 self-center">{{ modelValue }}/5</span>
  </div>
</template>

<script setup>
import { ref } from 'vue'
defineProps({ modelValue: { type: Number, default: 0 }, readonly: { type: Boolean, default: true }, showValue: Boolean })
defineEmits(['update:modelValue'])
const hovered = ref(0)
</script>
