<template>
  <div class="dashboard-table-wrap">
    <div v-if="searchable" class="dashboard-table-search">
      <input v-model="search" type="text" placeholder="🔍 Rechercher..." class="input-field max-w-sm text-sm" />
    </div>
    <div class="overflow-x-auto">
      <table class="dashboard-table">
        <thead>
          <tr>
            <th v-for="col in columns" :key="col.key"
              class="dashboard-th">
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody v-if="loading">
          <tr v-for="n in 5" :key="`skeleton-row-${n}`" class="dashboard-skeleton-row">
            <td :colspan="columns.length" class="dashboard-td">
              <div class="dashboard-skeleton-line"></div>
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr v-for="row in filteredData" :key="row._id || row.id || JSON.stringify(row)"
            class="dashboard-row">
            <td v-for="col in columns" :key="col.key" class="dashboard-td">
              <slot :name="col.key" :row="row">
                {{ row[col.key] ?? '—' }}
              </slot>
            </td>
          </tr>
          <tr v-if="!filteredData.length">
            <td :colspan="columns.length" class="dashboard-empty">
              <p class="text-2xl mb-2">📋</p>
              Aucune donnée
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="!loading && filteredData.length > 0" class="dashboard-table-footer">
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
  loading: { type: Boolean, default: false },
})
const search = ref('')
const filteredData = computed(() => {
  if (!search.value) return props.data
  const q = search.value.toLowerCase()
  return props.data.filter(row => Object.values(row).some(v => String(v).toLowerCase().includes(q)))
})
</script>

<style scoped>
.dashboard-table-wrap {
  border: 1px solid var(--border);
  border-radius: 20px;
  overflow: hidden;
  background: var(--bg-secondary);
}

.dashboard-table-search {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
}

.dashboard-table {
  width: 100%;
  border-collapse: collapse;
}

.dashboard-th {
  background: rgba(255, 255, 255, 0.04);
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 12px;
  font-weight: 700;
  text-align: left;
  padding: 14px 24px;
  white-space: nowrap;
}

.dashboard-row {
  border-bottom: 1px solid var(--border);
  opacity: 0;
  animation: rowFadeIn 0.35s ease forwards;
}

.dashboard-row:nth-child(1) { animation-delay: 0.05s; }
.dashboard-row:nth-child(2) { animation-delay: 0.1s; }
.dashboard-row:nth-child(3) { animation-delay: 0.15s; }
.dashboard-row:nth-child(4) { animation-delay: 0.2s; }
.dashboard-row:nth-child(5) { animation-delay: 0.25s; }

.dashboard-row:hover {
  background: rgba(255, 255, 255, 0.03);
}

.dashboard-td {
  padding: 16px 24px;
  color: var(--text-primary);
  font-size: 14px;
  white-space: nowrap;
}

.dashboard-empty {
  text-align: center;
  padding: 24px;
  color: var(--text-secondary);
}

.dashboard-table-footer {
  border-top: 1px solid var(--border);
  padding: 12px 20px;
  text-align: right;
  color: var(--text-secondary);
  font-size: 12px;
}

.dashboard-skeleton-row {
  border-bottom: 1px solid var(--border);
}

.dashboard-skeleton-line {
  height: 14px;
  border-radius: 999px;
  width: 100%;
  background: linear-gradient(90deg, rgba(148, 163, 184, 0.16), rgba(148, 163, 184, 0.34), rgba(148, 163, 184, 0.16));
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@keyframes rowFadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
