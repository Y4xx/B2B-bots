import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { User, Tenant } from '../types'

interface AuthState {
  user: User | null
  tenant: Tenant | null
  token: string | null
  setAuth: (user: User, tenant: Tenant, token: string) => void
  logout: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      tenant: null,
      token: null,
      setAuth: (user, tenant, token) => {
        localStorage.setItem('token', token)
        set({ user, tenant, token })
      },
      logout: () => {
        localStorage.removeItem('token')
        set({ user: null, tenant: null, token: null })
      },
    }),
    {
      name: 'auth-storage',
    }
  )
)
