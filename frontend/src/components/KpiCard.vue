<template>
  <div class="dashboard-kpi-card" :style="cardStyle" ref="cardRef">
    <div class="dashboard-kpi-head">
      <div class="dashboard-kpi-icon">
        <component v-if="icon && typeof icon !== 'string'" :is="icon" :size="26" :color="'currentColor'" />
        <span v-else>{{ icon }}</span>
      </div>
    </div>

    <div v-if="loading" class="dashboard-kpi-skeleton"></div>
    <p v-else class="dashboard-kpi-value">{{ displayValue }}</p>
    <p class="dashboard-kpi-label">{{ label }}</p>

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
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  icon: { type: [String, Object, Function], default: '' },
  label: String,
  value: [String, Number],
  trend: Number,
  loading: { type: Boolean, default: false },
  color: { type: String, default: 'gold' },
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

const themePresets = {
  blue: { start: '#4F46E5', end: '#7C3AED' },
  green: { start: '#10B981', end: '#14B8A6' },
  gold: { start: '#F59E0B', end: '#FB923C' },
  purple: { start: '#8B5CF6', end: '#A855F7' },
}

const cardStyle = computed(() => {
  const preset = themePresets[props.color] || themePresets.gold
  return {
    '--stat-accent': '#F59E0B',
    '--stat-icon-start': preset.start,
    '--stat-icon-end': preset.end,
    '--stat-icon-fg': '#FFFFFF',
    '--stat-shadow': `0 14px 28px ${preset.start}22`,
  }
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

// Re-run animation when the value changes (e.g., after async API response)
watch(numericTarget, (newVal, oldVal) => {
  if (newVal !== null && newVal !== oldVal) {
    hasAnimated.value = false
    runCountUp()
  }
})

onBeforeUnmount(() => {
  observer?.disconnect()
})
</script>

<style scoped>
.dashboard-kpi-card {
  position: relative;
  border-radius: 30px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(250, 250, 250, 0.98) 100%);
  padding: 24px;
  overflow: hidden;
  opacity: 0;
  min-height: 275px;
  box-shadow: var(--stat-shadow, 0 12px 28px rgba(58, 26, 4, 0.08));
  animation: kpiReveal 0.45s ease forwards;
  transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
}

.dashboard-kpi-head {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.8rem;
}

.dashboard-kpi-icon {
  width: 58px;
  height: 58px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--stat-icon-start, #f59e0b), var(--stat-icon-end, #fb923c));
  color: var(--stat-icon-fg, #fff);
  box-shadow: 0 12px 22px rgba(245, 158, 11, 0.18);
}

.dashboard-kpi-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 18px 38px rgba(58, 26, 4, 0.12);
  border-color: rgba(245, 158, 11, 0.28);
}

.dashboard-kpi-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, color-mix(in srgb, var(--stat-icon-start) 12%, transparent) 0%, transparent 52%);
  pointer-events: none;
}

.dashboard-kpi-card::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 4px;
  background: #F59E0B;
}

.dashboard-kpi-card > * {
  position: relative;
  z-index: 1;
}

.dashboard-kpi-label {
  color: #8B9BB8;
  font-size: 17px;
  line-height: 1.35;
  margin: 8px 0 0;
}

.dashboard-kpi-value {
  margin-top: 18px;
  color: #1F2D5A;
  font-size: 38px;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.04em;
}

.dashboard-kpi-trend {
  margin-top: 26px;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 14px;
  font-weight: 700;
  padding: 10px 16px;
  border-radius: 999px;
}

.dashboard-kpi-trend-main {
  font-weight: 700;
}

.dashboard-kpi-trend-sub {
  color: var(--text-secondary);
}

.trend-up {
  color: #16A34A;
  background: rgba(34, 197, 94, 0.12);
}

.trend-down {
  color: #DC2626;
  background: rgba(239, 68, 68, 0.12);
}

.dashboard-kpi-skeleton {
  margin-top: 18px;
  height: 54px;
  border-radius: 16px;
  background: linear-gradient(90deg, rgba(148, 163, 184, 0.16), rgba(148, 163, 184, 0.34), rgba(148, 163, 184, 0.16));
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.dashboard-kpi-accent {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 4px;
  background: #F59E0B;
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
