<template>
  <div class="register-container min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="register-wrapper w-full max-w-6xl grid lg:grid-cols-[0.9fr_1.1fr] rounded-[2.5rem] overflow-hidden shadow-[0_40px_100px_rgba(45,27,8,0.15)] bg-white">
      
      <!-- Section Hero (Luxe Visual) -->
      <aside class="hidden lg:flex relative bg-[#2D1B08] p-12 flex-col justify-between overflow-hidden">
        <div class="absolute inset-0 opacity-40">
          <img 
            src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1200&q=80" 
            class="w-full h-full object-cover" 
            alt="Luxury Resort"
          />
          <div class="absolute inset-0 bg-gradient-to-br from-[#2D1B08] via-transparent to-[#2D1B08]/80"></div>
        </div>

        <div class="relative z-10">
          <RouterLink to="/" class="inline-block transition-transform hover:scale-105 active:scale-95">
            <AppLogo variant="dark" size="lg" />
          </RouterLink>
        </div>

        <div class="relative z-10 max-w-sm">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 mb-6">
            <Sparkles :size="14" class="text-[#D4820A]" />
            <span class="text-[10px] font-bold text-white uppercase tracking-widest">Privilège HotelEase</span>
          </div>
          
          <h2 class="text-4xl xl:text-5xl font-serif font-bold text-white leading-tight mb-6">
            Devenez membre de <span class="text-[#D4820A]">l'élite</span>.
          </h2>
          <p class="text-white/70 text-lg font-medium leading-relaxed mb-8">
            Rejoignez notre cercle exclusif et profitez d'avantages personnalisés dès votre première réservation.
          </p>
          
          <div class="space-y-5">
            <div class="flex items-center gap-4 text-white/90">
              <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/10">
                <Trophy :size="20" class="text-[#D4820A]" />
              </div>
              <div>
                <p class="font-bold text-sm">Programme Fidélité</p>
                <p class="text-xs text-white/50">Cumulez des points à chaque séjour.</p>
              </div>
            </div>
            <div class="flex items-center gap-4 text-white/90">
              <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/10">
                <ShieldCheck :size="20" class="text-[#D4820A]" />
              </div>
              <div>
                <p class="font-bold text-sm">Meilleurs Tarifs Garantis</p>
                <p class="text-xs text-white/50">Offres exclusives réservées aux membres.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="relative z-10 text-white/40 text-xs font-bold tracking-[0.2em] uppercase">
          © {{ new Date().getFullYear() }} HotelEase • Conciergerie Digitale
        </div>
      </aside>

      <!-- Section Formulaire -->
      <main class="flex flex-col justify-center p-8 sm:p-12 lg:p-16 bg-white relative overflow-y-auto">
        <div class="w-full max-w-md mx-auto">
          <header class="mb-8">
            <h1 class="text-3xl font-serif font-bold text-[#2D1B08] mb-2">Inscription</h1>
            <p class="text-slate-500 font-medium text-sm">Créez votre compte en quelques instants.</p>
          </header>

          <div v-if="error" class="error-alert mb-6 animate-shake">
            <div class="flex items-center gap-3">
              <AlertCircle :size="18" />
              <span>{{ error }}</span>
            </div>
          </div>

          <form @submit.prevent="handleRegister" class="space-y-5">
            <!-- Nom / Prénom -->
            <div class="grid grid-cols-2 gap-4">
              <div class="group">
                <label class="block text-[0.65rem] font-black uppercase tracking-[0.15em] text-[#2D1B08] mb-1.5 opacity-60">Prénom</label>
                <div class="relative">
                  <User :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#D4820A] transition-colors" />
                  <input v-model="form.prenom" type="text" placeholder="Jean" class="auth-input" required />
                </div>
              </div>
              <div class="group">
                <label class="block text-[0.65rem] font-black uppercase tracking-[0.15em] text-[#2D1B08] mb-1.5 opacity-60">Nom</label>
                <div class="relative">
                  <User :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#D4820A] transition-colors" />
                  <input v-model="form.nom" type="text" placeholder="Dupont" class="auth-input" required />
                </div>
              </div>
            </div>

            <!-- Email -->
            <div class="group">
              <label class="block text-[0.65rem] font-black uppercase tracking-[0.15em] text-[#2D1B08] mb-1.5 opacity-60">Adresse Email</label>
              <div class="relative">
                <Mail :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#D4820A] transition-colors" />
                <input v-model="form.email" type="email" placeholder="jean.dupont@email.com" class="auth-input" required />
              </div>
            </div>

            <!-- Téléphone -->
            <div class="group">
              <label class="block text-[0.65rem] font-black uppercase tracking-[0.15em] text-[#2D1B08] mb-1.5 opacity-60">Téléphone</label>
              <div class="relative">
                <Phone :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#D4820A] transition-colors" />
                <input v-model="form.telephone" type="tel" placeholder="+33 6 00 00 00 00" class="auth-input" />
              </div>
            </div>

            <!-- Mot de passe -->
            <div class="group">
              <label class="block text-[0.65rem] font-black uppercase tracking-[0.15em] text-[#2D1B08] mb-1.5 opacity-60">Mot de passe</label>
              <div class="relative">
                <Lock :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#D4820A] transition-colors" />
                <input 
                  v-model="form.password" 
                  :type="showPwd ? 'text' : 'password'" 
                  placeholder="Min. 8 caractères"
                  class="auth-input pr-12" 
                  required 
                />
                <button type="button" @click="showPwd = !showPwd" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#D4820A]">
                  <Eye v-if="!showPwd" :size="16" />
                  <EyeOff v-else :size="16" />
                </button>
              </div>
            </div>

            <!-- Confirmation -->
            <div class="group">
              <label class="block text-[0.65rem] font-black uppercase tracking-[0.15em] text-[#2D1B08] mb-1.5 opacity-60">Confirmer le mot de passe</label>
              <div class="relative">
                <Lock :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#D4820A] transition-colors" />
                <input 
                  v-model="form.password_confirmation" 
                  :type="showPwd ? 'text' : 'password'" 
                  class="auth-input pr-12" 
                  required 
                />
              </div>
            </div>

            <div class="pt-2">
              <label class="relative flex items-start gap-3 cursor-pointer group">
                <div class="relative flex items-center mt-1">
                  <input type="checkbox" v-model="form.accept" class="peer hidden" required />
                  <div class="w-5 h-5 border-2 border-slate-200 rounded-lg bg-white peer-checked:bg-[#D4820A] peer-checked:border-[#D4820A] transition-all flex items-center justify-center">
                    <Check :size="14" class="text-white scale-0 peer-checked:scale-100 transition-transform" />
                  </div>
                </div>
                <span class="text-xs font-semibold text-slate-500 leading-relaxed">
                  J'accepte les <a href="#" class="text-[#D4820A] hover:underline">Conditions Générales</a> et la <a href="#" class="text-[#D4820A] hover:underline">Politique de Confidentialité</a>.
                </span>
              </label>
            </div>

            <button 
              type="submit" 
              :disabled="loading || form.password !== form.password_confirmation" 
              class="w-full py-4 bg-[#2D1B08] hover:bg-[#3D2B18] text-white rounded-2xl font-bold tracking-wide shadow-xl shadow-[#2D1B08]/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-3"
            >
              <div v-if="loading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              <template v-else>
                <UserPlus :size="20" />
                <span>Créer mon compte</span>
              </template>
            </button>
          </form>

          <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col items-center gap-5">
            <p class="text-sm text-slate-500 font-medium">
              Déjà membre ? 
              <RouterLink to="/login" class="text-[#D4820A] font-bold hover:underline">Se connecter</RouterLink>
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
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import AppLogo from '../components/AppLogo.vue'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import { 
  User, 
  Mail, 
  Lock, 
  Eye, 
  EyeOff, 
  Phone, 
  Check, 
  AlertCircle, 
  Sparkles, 
  Trophy, 
  ShieldCheck,
  UserPlus
} from 'lucide-vue-next'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const form = reactive({ 
  prenom: '', 
  nom: '', 
  email: '', 
  telephone: '', 
  password: '', 
  password_confirmation: '', 
  accept: false 
})

const error = ref('')
const loading = ref(false)
const showPwd = ref(false)

async function handleRegister() {
  if (form.password !== form.password_confirmation) {
    error.value = "Les mots de passe ne correspondent pas."
    return
  }
  error.value = ''
  loading.value = true
  try {
    await auth.register(form)
    await new Promise(resolve => setTimeout(resolve, 500))
    router.push('/dashboard/client/overview')
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || "Une erreur est survenue lors de l'inscription."
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700;800&display=swap');

.register-container {
  background-color: #fcfaf8;
  background-image: 
    radial-gradient(circle at 10% 20%, rgba(212, 130, 10, 0.03), transparent 25%),
    radial-gradient(circle at 90% 80%, rgba(45, 27, 8, 0.04), transparent 30%);
  font-family: 'DM Sans', sans-serif;
}

.font-serif {
  font-family: 'Playfair Display', serif;
}

.auth-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.75rem;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 1rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #2D1B08;
  outline: none;
  transition: all 0.2s ease;
}

.auth-input:focus {
  background: white;
  border-color: #D4820A;
  box-shadow: 0 0 0 4px rgba(212, 130, 10, 0.1);
}

.error-alert {
  background: #fef2f2;
  border: 1px solid #fee2e2;
  color: #b91c1c;
  padding: 0.85rem;
  border-radius: 1rem;
  font-size: 0.8rem;
  font-weight: 600;
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

aside {
  min-height: 700px;
}

@media (max-width: 1023px) {
  .register-wrapper {
    max-width: 480px;
    border-radius: 2rem;
  }
}
</style>
