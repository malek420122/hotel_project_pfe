<template>
  <div class="dashboard-kpi-card" ref="cardRef">
    <div class="dashboard-kpi-head">
      <div class="dashboard-kpi-icon">{{ icon }}</div>
      <p class="dashboard-kpi-label">{{ label }}</p>
    </div>

    <div v-if="loading" class="dashboard-kpi-skeleton"></div>
    <p v-else class="dashboard-kpi-value">{{ displayValue }}</p>

    <div v-if="trend !== undefined" class="dashboard-kpi-trend" :class="trend >= 0 ? 'trend-up' : 'trend-down'">
      <span class="dashboard-kpi-trend-main">
        {{ trend >= 0 ? '↑' : '↓' }} {{ Math.abs(trend) }}%
      </span>
      <span class="dashboard-kpi-trend-sub">vs mois dernier</span>
    </div>

    <div class="dashboard-kpi-accent"></div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  icon: String,
  label: String,
  value: [String, Number],
  trend: Number,
  loading: { type: Boolean, default: false },
})

const cardRef = ref(null)
const animated = ref(0)
const hasAnimated = ref(false)
let observer = null

const numericTarget = computed(() => {
  if (typeof props.value === 'number') return props.value
  const clean = String(props.value ?? '').replace(/[^0-9.\-]/g, '')
  const parsed = Number(clean)
  return Number.isFinite(parsed) ? parsed : null
})

const displayValue = computed(() => {
  if (numericTarget.value === null) return props.value
  if (typeof props.value === 'string' && props.value.includes('%')) return `${animated.value.toFixed(1)}%`
  if (typeof props.value === 'string' && props.value.includes('€')) return `${Math.round(animated.value)}€`
  return Math.round(animated.value)
})

function runCountUp() {
  if (numericTarget.value === null || hasAnimated.value) return
  hasAnimated.value = true

  const target = numericTarget.value
  const start = performance.now()
  const from = 0
  const duration = 900

  const step = (now) => {
    const progress = Math.min(1, (now - start) / duration)
    animated.value = from + (target - from) * (1 - Math.pow(1 - progress, 3))
    if (progress < 1) requestAnimationFrame(step)
  }

  requestAnimationFrame(step)
}

onMounted(() => {
  if (!cardRef.value || numericTarget.value === null) {
    animated.value = numericTarget.value ?? 0
    return
  }

  observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        runCountUp()
        observer?.disconnect()
      }
    })
  }, { threshold: 0.4 })

  observer.observe(cardRef.value)
})

onBeforeUnmount(() => {
  observer?.disconnect()
})
</script>

<style scoped>
.dashboard-kpi-card {
  position: relative;
  border-radius: 20px;
  border: 1px solid var(--border);
  background: var(--bg-card);
  padding: 24px;
  overflow: hidden;
  opacity: 0;
  animation: kpiReveal 0.45s ease forwards;
}

.dashboard-kpi-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
}

.dashboard-kpi-icon {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(245, 158, 11, 0.16);
  color: var(--gold-light);
}

.dashboard-kpi-label {
  color: var(--text-secondary);
  font-size: 14px;
  margin: 0;
}

.dashboard-kpi-value {
  margin-top: 0.9rem;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 800;
  line-height: 1.1;
}

.dashboard-kpi-trend {
  margin-top: 0.55rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 12px;
}

.dashboard-kpi-trend-main {
  font-weight: 700;
}

.dashboard-kpi-trend-sub {
  color: var(--text-secondary);
}

.trend-up {
  color: var(--success);
}

.trend-down {
  color: var(--danger);
}

.dashboard-kpi-skeleton {
  margin-top: 0.8rem;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(90deg, rgba(148, 163, 184, 0.16), rgba(148, 163, 184, 0.34), rgba(148, 163, 184, 0.16));
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.dashboard-kpi-accent {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--gold), var(--gold-light), transparent);
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@keyframes kpiReveal {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
