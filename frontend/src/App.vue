<template>
  <div class="app">
    <header class="app-header">
      <div class="brand">
        <span class="logo">🏨</span>
        <div>
          <h1>{{ $t('app.name') }}</h1>
          <p>{{ $t('app.tagline') }}</p>
        </div>
      </div>
      <nav>
        <RouterLink to="/">{{ $t('nav.home') }}</RouterLink>
        <RouterLink to="/rooms">{{ $t('nav.rooms') }}</RouterLink>
        <RouterLink to="/services">{{ $t('nav.services') }}</RouterLink>
        <RouterLink to="/booking">{{ $t('nav.booking') }}</RouterLink>
        <RouterLink to="/my-bookings">{{ $t('nav.myBookings') }}</RouterLink>
        <RouterLink to="/admin">{{ $t('nav.admin') }}</RouterLink>
      </nav>
      <LanguageSwitcher />
    </header>

    <main class="content">
      <RouterView />
    </main>

    <footer class="app-footer">
      <p>© 2026 Hotel PFE • Support 24/7</p>
    </footer>
  </div>
</template>

<script setup>
import LanguageSwitcher from './components/LanguageSwitcher.vue'
</script>

<style scoped>
.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.app-header {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  align-items: center;
  padding: 1.5rem 2rem;
  background: #0f172a;
  color: #fff;
}

.brand {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.brand h1 {
  margin: 0;
  font-size: 1.4rem;
}

.brand p {
  margin: 0;
  color: #cbd5f5;
}

nav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
}

nav a {
  color: #e2e8f0;
  text-decoration: none;
  font-weight: 600;
}

nav a.router-link-active {
  color: #38bdf8;
}

.content {
  flex: 1;
  padding: 2rem;
  background: #f8fafc;
}

.app-footer {
  text-align: center;
  padding: 1rem;
  background: #0f172a;
  color: #cbd5f5;
}
</style>
