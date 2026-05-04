<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('reception.rooms.title') }}</h2>

    <div class="card mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('reception.rooms.hotel') }}</label>
          <select v-model="selectedHotelId" class="input-field" @change="loadRooms">
            <option v-for="hotel in hotels" :key="hotel._id" :value="hotel._id">{{ hotel.nom }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ t('reception.rooms.floor') }}</label>
          <select v-model="selectedFloor" class="input-field">
            <option value="all">{{ t('reception.rooms.allFloors') }}</option>
            <option v-for="floor in floors" :key="floor" :value="String(floor)">{{ t('reception.rooms.floorPrefix') }} {{ floor }}</option>
          </select>
        </div>
      </div>

      <div class="legend-bar">
        <div v-for="s in statuses" :key="s.val" class="flex items-center gap-2">
          <div :class="['w-3.5 h-3.5 rounded-full', s.color]"></div>
          <span class="text-sm text-gray-600">{{ s.label }}</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="card space-y-3">
      <div v-for="n in 6" :key="n" class="h-28 rounded-2xl bg-white/5 animate-pulse"></div>
    </div>

    <div v-else-if="visibleFloors.length" class="card">
      <div v-for="floor in visibleFloors" :key="floor" class="mb-6 last:mb-0">
        <h3 class="text-sm font-bold text-gray-500 mb-3">{{ t('reception.rooms.floorPrefix') }} {{ floor }}</h3>
        <div class="flex flex-wrap gap-3">
          <div v-for="room in roomsByFloor(floor)" :key="room._id"
            :class="['room-card cursor-pointer transition-all hover:-translate-y-0.5 hover:shadow-lg border', roomColor(room)]"
            @click="selectRoom(room)">
            <span :class="['room-dot', roomDot(room)]"></span>
            <span class="room-number">{{ displayRoomNumber(room) }}</span>
            <span class="room-type">{{ room.type }}</span>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="card text-center py-10 text-gray-400">{{ t('reception.rooms.noRooms') }}</div>

    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="selected = null">
        <div class="bg-white rounded-xl p-6 w-80 shadow-2xl">
          <h3 class="text-lg font-bold mb-3">{{ t('reception.rooms.room') }} {{ displayRoomNumber(selected) }}</h3>
          <p>{{ t('reception.rooms.type') }}: {{ selected.type }}</p>
          <p>{{ t('reception.rooms.status') }}: {{ selected.statut || (selected.estDisponible ? 'LIBRE' : 'OCCUPE') }}</p>
          <p v-if="selected.hotel">{{ t('reception.rooms.hotel') }}: {{ selected.hotel.nom }}</p>
          <button @click="selected = null" class="btn-outline w-full mt-4">{{ t('common.close') }}</button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'

const { t } = useI18n()
const selected = ref(null)
const rooms = ref([])
const hotels = ref([])
const selectedHotelId = ref('')
const selectedFloor = ref('all')
const loading = ref(true)

const floors = computed(() => [...new Set(rooms.value.map((room) => Number(room.etage || 0)).filter(Boolean))].sort((a, b) => a - b))
const visibleFloors = computed(() => selectedFloor.value === 'all' ? floors.value : [Number(selectedFloor.value)])

const statuses = computed(() => {
  const map = {
    LIBRE: { val: 'LIBRE', label: t('reception.rooms.free'), color: 'bg-green-400' },
    OCCUPE: { val: 'OCCUPE', label: t('reception.rooms.occupied'), color: 'bg-red-400' },
    NETTOYAGE: { val: 'NETTOYAGE', label: t('reception.rooms.cleaning'), color: 'bg-yellow-400' },
    ENTRETIEN: { val: 'ENTRETIEN', label: t('reception.rooms.maintenance'), color: 'bg-gray-400' },
  }

  return Object.values(map).filter((status) => rooms.value.some((room) => (room.statut || 'LIBRE') === status.val))
})

function roomsByFloor(floor) {
  return rooms.value.filter((room) => Number(room.etage || 0) === Number(floor) && (selectedHotelId.value === '' || String(room.hotelId || '') === selectedHotelId.value))
}

function roomColor(room) {
  const stat = room.statut || (room.estDisponible ? 'LIBRE' : 'OCCUPE')
  const map = {
    LIBRE: 'border-green-400 bg-green-50 hover:border-green-500',
    OCCUPE: 'border-red-400 bg-red-50',
    NETTOYAGE: 'border-yellow-400 bg-yellow-50',
    ENTRETIEN: 'border-gray-400 bg-gray-50',
  }

  return map[stat] || 'border-gray-200 bg-white'
}

function roomDot(room) {
  const stat = room.statut || (room.estDisponible ? 'LIBRE' : 'OCCUPE')
  return stat === 'LIBRE' ? 'bg-green-400' : stat === 'OCCUPE' ? 'bg-red-400' : stat === 'NETTOYAGE' ? 'bg-yellow-400' : 'bg-gray-200'
}

function displayRoomNumber(room) {
  const numero = room?.numero || room?.room_number
  if (numero) {
    return String(numero)
  }

  const name = String(room?.nom || '')
  const digits = name.match(/\d+/g)
  if (digits?.length) {
    return digits[digits.length - 1]
  }

  return String(room?._id || '').slice(-4).toUpperCase()
}

function selectRoom(room) {
  selected.value = room
}

async function loadRooms() {
    try {
    loading.value = true
    const hotelsRes = await api.get('/hotels', { params: { per_page: 200 } })
    const hotelRows = Array.isArray(hotelsRes.data?.data) ? hotelsRes.data.data : (Array.isArray(hotelsRes.data) ? hotelsRes.data : [])
    hotels.value = hotelRows
    if (!selectedHotelId.value && hotelRows.length) {
      selectedHotelId.value = String(hotelRows[0]._id)
    }

    const roomsRes = await api.get('/chambres/grille', { params: selectedHotelId.value ? { hotelId: selectedHotelId.value } : {} })
    const rows = Array.isArray(roomsRes.data) ? roomsRes.data : []
    rooms.value = rows.map((room) => ({
      ...room,
      numero: room.numero || room.room_number || displayRoomNumber(room),
    }))
  } catch {
    rooms.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadRooms)
</script>

<style scoped>
.legend-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 18px;
  align-items: center;
  padding: 10px 14px;
  border-radius: 14px;
  background: var(--bg-card);
}

.room-card {
  width: 96px;
  height: 96px;
  border-radius: 18px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  padding: 10px;
  border-width: 2px;
}

.room-number {
  font-size: 1.15rem;
  font-weight: 800;
  line-height: 1;
}

.room-type {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.room-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
}
</style>
