import { createRouter, createWebHistory } from 'vue-router'

import Home from '../pages/Home.vue'
import Rooms from '../pages/Rooms.vue'
import Services from '../pages/Services.vue'
import Booking from '../pages/Booking.vue'
import MyBookings from '../pages/MyBookings.vue'
import AdminDashboard from '../pages/AdminDashboard.vue'

const routes = [
  { path: '/', name: 'home', component: Home },
  { path: '/rooms', name: 'rooms', component: Rooms },
  { path: '/services', name: 'services', component: Services },
  { path: '/booking', name: 'booking', component: Booking },
  { path: '/my-bookings', name: 'my-bookings', component: MyBookings },
  { path: '/admin', name: 'admin', component: AdminDashboard },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
