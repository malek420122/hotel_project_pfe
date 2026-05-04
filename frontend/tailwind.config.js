/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#D4820A',      // or chaud
        'primary-600': '#E09A2D',
        secondary: '#8B4513',    // brun
        accent: '#EFB84D',       // doré clair
        'bg-light': '#FAF6EE',
        card: 'rgba(255, 252, 244, 0.86)',
        text: '#3A1A04',
        muted: '#7B5A3B',
        'brand-brown': '#2D1B08',
        'brand-gold': '#D4820A',
        'brand-primary': '#8B4513',
      },
      fontFamily: {
        sans: ['DM Sans', 'system-ui', 'sans-serif'],
        inter: ['DM Sans', 'system-ui', 'sans-serif'],
        playfair: ['Playfair Display', 'serif'],
      },
    },
  },
  plugins: [],
}
