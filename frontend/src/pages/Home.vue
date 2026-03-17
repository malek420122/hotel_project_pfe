<template>
  <section class="hero">
    <div>
      <p class="badge">{{ $t('app.tagline') }}</p>
      <h1>{{ $t('home.heroTitle') }}</h1>
      <p class="subtitle">{{ $t('home.heroSubtitle') }}</p>
      <router-link class="primary" to="/booking">
        {{ $t('home.cta') }}
      </router-link>
    </div>
    <div class="hero-card">
      <h2>{{ $t('booking.title') }}</h2>
      <p>{{ $t('rooms.title') }}</p>
      <ul>
        <li>✓ {{ $t('home.feature1') }}</li>
        <li>✓ {{ $t('home.feature2') }}</li>
        <li>✓ {{ $t('home.feature3') }}</li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
.hero {
  display: grid;
  gap: 2rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  align-items: center;
  padding: 2rem 0;
}

.badge {
  background: #e0e7ff;
  color: #3730a3;
  padding: 0.4rem 0.8rem;
  border-radius: 999px;
  font-weight: 600;
  display: inline-block;
}

.subtitle {
  color: #4b5563;
  font-size: 1.1rem;
}

.primary {
  display: inline-flex;
  margin-top: 1rem;
  padding: 0.8rem 1.4rem;
  border-radius: 0.9rem;
  background: #1d4ed8;
  color: #fff;
  text-decoration: none;
  font-weight: 700;
}

.hero-card {
  background: #fff;
  padding: 1.5rem;
  border-radius: 1.2rem;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.hero-card ul {
  padding-left: 1rem;
  color: #374151;
}
</style>
