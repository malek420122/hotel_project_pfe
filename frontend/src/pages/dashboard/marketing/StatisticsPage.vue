<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Statistiques</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Réservations sur 12 mois</h3>
        <Line :data="lineData" :options="lineOpts" />
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Revenus par hotel</h3>
        <Bar :data="barData" :options="barOpts" />
      </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Sources de trafic</h3>
        <Doughnut :data="doughnutData" :options="doughnutOpts" />
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Taux d'occupation mensuel</h3>
        <Bar :data="occData" :options="occOpts" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { Line, Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend, Filler } from 'chart.js'
ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend, Filler)

const months = ['Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc','Jan','Fév','Mar']
const lineData = { labels: months, datasets: [{ label: 'Réservations', data: [180,220,350,480,510,290,240,210,270,300,330,384], borderColor:'#0071c2', backgroundColor:'rgba(0,113,194,0.1)', fill:true, tension:0.4 }] }
const barData = { labels: ['Atlas','Riad','Ibis','Sofitel','Kenzi'], datasets: [{ label: 'Revenus (€)', data: [28400,21200,18800,15600,12300], backgroundColor:['#003580','#0071c2','#FFB700','#10b981','#8b5cf6'], borderRadius:6 }] }
const doughnutData = { labels: ['Organique','Payant','Réseaux','Email','Direct'], datasets: [{ data: [35,25,20,12,8], backgroundColor:['#003580','#0071c2','#FFB700','#10b981','#8b5cf6'] }] }
const occData = { labels: months, datasets: [{ label: 'Taux occupation (%)', data: [72,78,85,95,97,82,76,74,80,83,87,91], backgroundColor: '#10b981', borderRadius:4 }] }
const lineOpts = { responsive:true, plugins:{legend:{position:'bottom'}} }
const barOpts = { responsive:true, plugins:{legend:{display:false}} }
const doughnutOpts = { responsive:true, plugins:{legend:{position:'bottom'}} }
const occOpts = { responsive:true, plugins:{legend:{display:false}}, scales:{y:{min:0,max:100}} }
</script>
