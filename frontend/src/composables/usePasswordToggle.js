import { ref } from 'vue'

const showOldPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

export function usePasswordToggle() {
  return {
    showOldPassword,
    showNewPassword,
    showConfirmPassword,
  }
}
