<template>
  <div class="login-container min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="login-wrapper w-full max-w-5xl grid lg:grid-cols-2 rounded-[2.5rem] overflow-hidden shadow-[0_40px_100px_rgba(45,27,8,0.15)] bg-white">
      
      <!-- Section Hero (Luxe Visual) -->
      <aside class="hidden lg:flex relative bg-[#2D1B08] p-12 flex-col justify-between overflow-hidden">
        <div class="absolute inset-0 opacity-40">
          <img 
            src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80" 
            class="w-full h-full object-cover" 
            alt="Luxury Hotel"
          />
          <div class="absolute inset-0 bg-gradient-to-br from-[#2D1B08] via-transparent to-[#2D1B08]/80"></div>
        </div>

        <div class="relative z-10">
          <RouterLink to="/" class="inline-block transition-transform hover:scale-105 active:scale-95">
            <AppLogo variant="dark" size="lg" />
          </RouterLink>
        </div>

        <div class="relative z-10 max-w-sm">
          <h2 class="text-4xl xl:text-5xl font-serif font-bold text-white leading-tight mb-6">
            L'excellence hôtelière commence <span class="text-[#D4820A]">ici</span>.
          </h2>
          <p class="text-white/70 text-lg font-medium leading-relaxed mb-8">
            Accédez à votre espace privilégié pour gérer vos séjours d'exception.
          </p>
          
          <div class="space-y-4">
            <div class="flex items-center gap-4 text-white/90">
              <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/10">
                <ShieldCheck :size="20" class="text-[#D4820A]" />
              </div>
              <span class="font-semibold text-sm">Accès Sécurisé & Chiffré</span>
            </div>
            <div class="flex items-center gap-4 text-white/90">
              <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/10">
                <Sparkles :size="20" class="text-[#D4820A]" />
              </div>
              <span class="font-semibold text-sm">Expérience Premium Personnalisée</span>
            </div>
          </div>
        </div>

        <div class="relative z-10 text-white/40 text-xs font-bold tracking-[0.2em] uppercase">
          © {{ new Date().getFullYear() }} HotelEase • All Rights Reserved
        </div>
      </aside>

      <!-- Section Formulaire -->
      <main class="flex flex-col justify-center p-8 sm:p-12 lg:p-16 bg-white relative">
        <div class="lg:hidden absolute top-8 left-8">
          <AppLogo variant="light" size="md" />
        </div>

        <div class="w-full max-w-sm mx-auto">
          <header class="mb-10">
            <h1 class="text-3xl font-serif font-bold text-[#2D1B08] mb-3">Connexion</h1>
            <p v-if="hasPendingBooking" class="text-[#D4820A] font-bold bg-orange-50 border border-orange-100 p-3 rounded-xl mb-4 text-sm">
              Connectez-vous pour finaliser votre réservation. Vos choix ont été conservés.
            </p>
            <p v-else class="text-slate-500 font-medium">Veuillez entrer vos accès pour continuer.</p>
          </header>

          <div v-if="error" :class="['error-alert mb-6 animate-shake', errorType === 'server' ? 'server-error' : '']">
            <div class="flex items-center gap-3">
              <AlertCircle :size="18" />
              <span>{{ error }}</span>
            </div>
          </div>

          <form @submit.prevent="handleLogin" class="space-y-6">
            <div class="group">
              <label class="block text-[0.7rem] font-black uppercase tracking-[0.15em] text-[#2D1B08] mb-2 opacity-60">
                Adresse Email
              </label>
              <div class="relative">
                <Mail :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#D4820A] transition-colors" />
                <input 
                  v-model="form.email" 
                  type="email" 
                  placeholder="votre@email.com"
                  class="w-full pl-12 pr-4 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:border-[#D4820A] focus:ring-4 focus:ring-[#D4820A]/10 outline-none transition-all font-medium text-[#2D1B08]"
                  required 
                />
              </div>
            </div>

            <div class="group">
              <div class="flex justify-between items-center mb-2">
                <label class="text-[0.7rem] font-black uppercase tracking-[0.15em] text-[#2D1B08] opacity-60">
                  Mot de passe
                </label>
                <a href="#" class="text-[0.7rem] font-bold text-[#D4820A] hover:underline uppercase tracking-wider">Oublié ?</a>
              </div>
              <div class="relative">
                <Lock :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#D4820A] transition-colors" />
                <input 
                  v-model="form.password" 
                  :type="showPwd ? 'text' : 'password'"
                  placeholder="••••••••"
                  class="w-full pl-12 pr-12 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:border-[#D4820A] focus:ring-4 focus:ring-[#D4820A]/10 outline-none transition-all font-medium text-[#2D1B08]"
                  required 
                />
                <button 
                  type="button" 
                  @click="showPwd = !showPwd"
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#D4820A] transition-colors"
                >
                  <Eye v-if="!showPwd" :size="18" />
                  <EyeOff v-else :size="18" />
                </button>
              </div>
            </div>

            <div class="flex items-center">
              <label class="relative flex items-center gap-3 cursor-pointer group">
                <div class="relative flex items-center">
                  <input type="checkbox" v-model="form.remember" class="peer hidden" />
                  <div class="w-5 h-5 border-2 border-slate-200 rounded-lg bg-white peer-checked:bg-[#D4820A] peer-checked:border-[#D4820A] transition-all flex items-center justify-center">
                    <Check :size="14" class="text-white scale-0 peer-checked:scale-100 transition-transform" />
                  </div>
                </div>
                <span class="text-sm font-semibold text-slate-600 group-hover:text-[#2D1B08] transition-colors">Rester connecté</span>
              </label>
            </div>

            <button 
              type="submit" 
              :disabled="loading" 
              class="w-full py-4 bg-[#2D1B08] hover:bg-[#3D2B18] text-white rounded-2xl font-bold tracking-wide shadow-xl shadow-[#2D1B08]/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-3"
            >
              <div v-if="loading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              <template v-else>
                <LogIn :size="20" />
                <span>Se Connecter</span>
              </template>
            </button>
          </form>

          <div class="mt-8 pt-8 border-t border-slate-100 flex flex-col items-center gap-6">
            <p class="text-sm text-slate-500 font-medium">
              Pas encore de compte ? 
              <RouterLink to="/register" class="text-[#D4820A] font-bold hover:underline">S'inscrire gratuitement</RouterLink>
            </p>
            
            <div class="w-full">
              <LanguageSwitcher />
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import AppLogo from '../components/AppLogo.vue'
import { 
  Lock, 
  Mail, 
  Eye, 
  EyeOff, 
  ShieldCheck, 
  Sparkles, 
  Check, 
  AlertCircle,
  LogIn
} from 'lucide-vue-next'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const form = reactive({ email: '', password: '', remember: false })
const error = ref('')
const errorType = ref('auth') // 'auth' or 'server'
const loading = ref(false)
const showPwd = ref(false)
const hasPendingBooking = ref(false)

onMounted(() => {
  hasPendingBooking.value = !!sessionStorage.getItem('postLoginRedirect')
})

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    const user = await auth.login(form)
    
    // Smooth transition
    await new Promise(resolve => setTimeout(resolve, 400))
    
    const queryRedirect = typeof route.query.redirect === 'string' ? route.query.redirect : ''
    const storedRedirect = sessionStorage.getItem('postLoginRedirect') || ''
    let redirect = queryRedirect || storedRedirect

    if (redirect && redirect.startsWith('/')) {
      sessionStorage.removeItem('postLoginRedirect')
      router.push(redirect)
      return
    }

    const roleMap = { 
      client: '/dashboard/client', 
      admin: '/dashboard/admin', 
      receptionniste: '/dashboard/receptionniste', 
      marketing: '/dashboard/marketing' 
    }
    router.push(roleMap[user.role] || '/')
  } catch (e) {
    if (!e.response) {
      errorType.value = 'server'
      error.value = "Problème réseau. Veuillez vérifier votre connexion."
    } else if (e.response?.status === 429) {
      errorType.value = 'server'
      error.value = "Trop de tentatives. Compte temporairement bloqué."
    } else if (e.response?.status >= 500) {
      errorType.value = 'server'
      error.value = "Erreur serveur, veuillez réessayer plus tard."
    } else {
      errorType.value = 'auth'
      error.value = e.response?.data?.message || "Identifiants invalides. Veuillez vérifier votre email et mot de passe."
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  showPwd.value = false
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700;800&display=swap');

.login-container {
  background-color: #fcfaf8;
  background-image: 
    radial-gradient(circle at 10% 20%, rgba(212, 130, 10, 0.03), transparent 25%),
    radial-gradient(circle at 90% 80%, rgba(45, 27, 8, 0.04), transparent 30%);
  font-family: 'DM Sans', sans-serif;
}

.font-serif {
  font-family: 'Playfair Display', serif;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-8px); }
  50% { transform: translateX(8px); }
  75% { transform: translateX(-4px); }
}

.animate-shake {
  animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
}

.error-alert {
  background: #fef2f2;
  border: 1px solid #fee2e2;
  color: #b91c1c;
  padding: 1rem;
  border-radius: 1rem;
  font-size: 0.85rem;
  font-weight: 600;
}

.error-alert.server-error {
  background: #fff7ed;
  border: 1px solid #ffedd5;
  color: #c2410c;
}

/* Custom transitions for inputs */
input:focus {
  transform: translateY(-1px);
}

aside {
  min-height: 650px;
}

@media (max-width: 1023px) {
  .login-wrapper {
    max-width: 450px;
    border-radius: 2rem;
  }
}
</style>
