<template>
  <div class="loyalty-container">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('dashboard.loyaltyProgram') }}</h2>
    
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
      <KpiCard :icon="Medal" :label="t('dashboard.membersBronze')" :value="kpis.Bronze ?? 0" color="blue" />
      <KpiCard :icon="Medal" :label="t('dashboard.membersSilver')" :value="kpis.Argent ?? 0" color="green" />
      <KpiCard :icon="Medal" :label="t('dashboard.membersGold')" :value="kpis.Or ?? 0" color="gold" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="premium-card">
        <h3 class="text-lg font-bold text-gray-800 mb-6">{{ t('dashboard.levelDistribution') }}</h3>
        <div class="chart-container">
          <Doughnut :data="chartData" :options="opts" />
        </div>
      </div>

      <div class="premium-card top-members-card">
        <h3 class="text-lg font-bold text-gray-800 mb-6">{{ t('dashboard.top_loyal_members') }}</h3>
        <div class="members-list">
          <div v-for="(m, i) in topMembers" :key="m.name" class="member-row">
            <div class="rank-icon-wrap">
              <template v-if="i === 0">
                <div class="ribbon"></div>
                <div class="medal-icon medal-gold">1</div>
              </template>
              <template v-else-if="i === 1">
                <div class="ribbon"></div>
                <div class="medal-icon medal-silver">2</div>
              </template>
              <template v-else-if="i === 2">
                <div class="ribbon"></div>
                <div class="medal-icon medal-bronze">3</div>
              </template>
              <template v-else>
                <div class="rank-box">{{ i + 1 }}</div>
              </template>
            </div>

            <div :class="['avatar-ring', getRingClass(i)]">
              <div class="avatar-bg">
                <img v-if="m.photo" :src="m.photo" :alt="m.name" class="avatar-img" />
                <span v-else>{{ m.name[0] }}</span>
              </div>
            </div>

            <div class="member-info">
              <p class="member-name">{{ m.name }}</p>
              <p class="member-stats">{{ m.pts }} points · {{ m.sejours }} séjours</p>
            </div>

            <div class="badge-wrap">
              <span :class="['level-badge', getLevelClass(m.level)]">{{ m.level }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import api from '../../../api'
import KpiCard from '../../../components/KpiCard.vue'
import { Medal } from 'lucide-vue-next'

ChartJS.register(ArcElement, Tooltip, Legend)

const { t } = useI18n()

const analytics = ref({ kpis: {}, levels: [], topMembers: [] })
const kpis = computed(() => analytics.value?.kpis || {})
const topMembers = computed(() => analytics.value?.topMembers || [])

const chartData = computed(() => ({
  labels: ['Bronze', 'Argent', 'Or'],
  datasets: [{ 
    data: [kpis.value.Bronze || 0, kpis.value.Argent || 0, kpis.value.Or || 0], 
    backgroundColor: ['#cd7f32', '#C0C0C0', '#FFD700'],
    borderWidth: 0,
    hoverOffset: 4
  }],
}))

const opts = { 
  responsive: true, 
  maintainAspectRatio: false,
  plugins: { 
    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } 
  } 
}

function getRingClass(i) {
  if (i === 0) return 'ring-gold'
  if (i === 1) return 'ring-silver'
  if (i === 2) return 'ring-bronze'
  return 'ring-blue'
}

function getLevelClass(level) {
  if (level === 'Or' || level === 'Gold') return 'badge-gold'
  if (level === 'Argent' || level === 'Silver') return 'badge-silver'
  return 'badge-bronze'
}

async function loadAnalytics() {
  try {
    const { data } = await api.get('/marketing/loyalty')
    analytics.value = {
      kpis: data?.kpis || {},
      levels: data?.levels || [],
      topMembers: data?.topMembers || [],
    }
  } catch {
    analytics.value = { kpis: {}, levels: [], topMembers: [] }
  }
}

onMounted(loadAnalytics)
</script>

<style scoped>
.loyalty-container {
  max-width: 1200px;
  margin: 0 auto;
}

.premium-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.03);
}

.chart-container {
  height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.members-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.member-row {
  display: flex;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.02);
}

.member-row:last-child {
  border-bottom: none;
}

/* Top Members Section Enhancements */
.top-members-card {
  background: linear-gradient(145deg, #ffffff, #f9fafb);
  position: relative;
  overflow: hidden;
}

.top-members-card::before {
  content: '';
  position: absolute;
  top: -50px;
  right: -50px;
  width: 150px;
  height: 150px;
  background: radial-gradient(circle, rgba(255, 215, 0, 0.05) 0%, transparent 70%);
  pointer-events: none;
}

.members-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.member-row {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-radius: 16px;
  background: transparent;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid transparent;
}

.member-row:hover {
  background: #ffffff;
  transform: scale(1.02) translateX(5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
  border-color: rgba(0, 0, 0, 0.03);
}

.rank-icon-wrap {
  width: 65px;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-right: 16px;
  position: relative;
  padding-top: 12px;
}

/* Medal Styles */
.medal-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 20px;
  position: relative;
  z-index: 2;
  box-shadow: 
    0 5px 12px rgba(0,0,0,0.25),
    inset 0 -2px 5px rgba(0,0,0,0.2),
    inset 0 2px 5px rgba(255,255,255,0.6);
  border: 2px solid rgba(255,255,255,0.4);
}

/* 3D Inner ring */
.medal-icon::before {
  content: '';
  position: absolute;
  inset: 4px;
  border-radius: 50%;
  border: 2px solid rgba(0,0,0,0.05);
  background: inherit;
  filter: brightness(1.15);
  z-index: -1;
}

.medal-gold {
  background: radial-gradient(circle at 35% 35%, #FFF5CC, #F9D423 50%, #B8860B);
  color: #5C4033;
}

.medal-silver {
  background: radial-gradient(circle at 35% 35%, #FFFFFF, #E5E7EB 50%, #9CA3AF);
  color: #374151;
}

.medal-bronze {
  background: radial-gradient(circle at 35% 35%, #FFE4D3, #CD7F32 50%, #8B4513);
  color: #431407;
}

/* Ribbon effect */
.ribbon {
  position: absolute;
  top: -10px;
  width: 38px;
  height: 34px;
  background: linear-gradient(to bottom, #E63946, #B91C1C);
  clip-path: polygon(0% 0%, 100% 0%, 85% 100%, 50% 85%, 15% 100%);
  z-index: 1;
  display: flex;
  justify-content: space-between;
  padding: 0 6px;
  border: 1px solid rgba(0,0,0,0.1);
}

.ribbon::before, .ribbon::after {
  content: '';
  width: 5px;
  height: 100%;
  background: rgba(255,255,255,0.8);
}

.rank-box {
  background: linear-gradient(135deg, #3B82F6, #1D4ED8);
  color: white;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 17px;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
  border: 1.5px solid rgba(255,255,255,0.2);
}

.avatar-ring {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3px;
  margin-right: 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.ring-gold { background: linear-gradient(135deg, #FDE047, #CA8A04); }
.ring-silver { background: linear-gradient(135deg, #F3F4F6, #9CA3AF); }
.ring-bronze { background: linear-gradient(135deg, #FB923C, #C2410C); }
.ring-blue { background: linear-gradient(135deg, #60A5FA, #2563EB); }

.avatar-bg {
  width: 100%;
  height: 100%;
  background: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 2px solid white;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-info {
  flex: 1;
}

.member-name {
  font-weight: 800;
  color: #111827;
  font-size: 16px;
  letter-spacing: -0.01em;
}

.member-stats {
  font-size: 13px;
  color: #6b7280;
  margin-top: 1px;
  font-weight: 500;
}

.badge-wrap {
  width: 90px;
  display: flex;
  justify-content: flex-end;
}

.level-badge {
  padding: 7px 18px;
  border-radius: 24px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  border: 1px solid rgba(255,255,255,0.3);
  transition: all 0.3s ease;
}

.badge-gold {
  background: linear-gradient(180deg, #FFD966 0%, #D4A017 100%);
  color: #5C4033;
}

.badge-silver {
  background: linear-gradient(180deg, #E5E7EB 0%, #9CA3AF 100%);
  color: #374151;
}

.badge-bronze {
  background: linear-gradient(180deg, #FDBA74 0%, #C2410C 100%);
  color: #431407;
}

/* Animations */
.member-row {
  animation: premiumIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
  opacity: 0;
}

@keyframes premiumIn {
  from { 
    transform: translateY(20px) scale(0.95);
    opacity: 0; 
  }
  to { 
    transform: translateY(0) scale(1);
    opacity: 1; 
  }
}

.member-row:nth-child(1) { animation-delay: 0.1s; }
.member-row:nth-child(2) { animation-delay: 0.2s; }
.member-row:nth-child(3) { animation-delay: 0.3s; }
.member-row:nth-child(4) { animation-delay: 0.4s; }
.member-row:nth-child(5) { animation-delay: 0.5s; }
</style>
