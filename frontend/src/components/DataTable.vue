<template>
  <div class="card overflow-hidden">
    <div v-if="searchable" class="p-4 border-b">
      <input v-model="search" type="text" placeholder="🔍 Rechercher..." class="input-field max-w-sm text-sm" />
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th v-for="col in columns" :key="col.key"
              class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="row in filteredData" :key="row._id || row.id || JSON.stringify(row)"
            class="hover:bg-gray-50 transition-colors">
            <td v-for="col in columns" :key="col.key" class="px-4 py-3 whitespace-nowrap">
              <slot :name="col.key" :row="row">
                {{ row[col.key] ?? '—' }}
              </slot>
            </td>
          </tr>
          <tr v-if="!filteredData.length">
            <td :colspan="columns.length" class="px-4 py-10 text-center text-gray-400">
              <p class="text-2xl mb-2">📋</p>
              Aucune donnée
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="filteredData.length > 0" class="p-3 border-t text-xs text-gray-400 text-right">
      {{ filteredData.length }} résultat(s)
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
const props = defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  searchable: { type: Boolean, default: true },
})
const search = ref('')
const filteredData = computed(() => {
  if (!search.value) return props.data
  const q = search.value.toLowerCase()
  return props.data.filter(row => Object.values(row).some(v => String(v).toLowerCase().includes(q)))
})
</script>
