import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService from '../auth/authService'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(null)
  const loading = ref(false)
  const error = ref(null)

  // Initialize from localStorage
  const initializeAuth = () => {
    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('user')
    
    if (storedToken && storedUser) {
      token.value = storedToken
      user.value = JSON.parse(storedUser)
    }
  }

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const currentUser = computed(() => user.value)
  const userFullName = computed(() => {
    if (!user.value) return ''
    return `${user.value.name} ${user.value.surname}`
  })

  // Actions
  const setAuth = (authToken, userData) => {
    token.value = authToken
    user.value = userData
    authService.setAuth(authToken, userData)
  }

  const clearAuth = () => {
    token.value = null
    user.value = null
    error.value = null
    authService.clearAuth()
  }

  const setLoading = (value) => {
    loading.value = value
  }

  const setError = (errorMessage) => {
    error.value = errorMessage
  }

  const clearError = () => {
    error.value = null
  }

  // Login action
  const login = async (credentials) => {
    try {
      setLoading(true)
      clearError()
      
      const response = await authService.login(credentials)
      setAuth(response.token, response.user)
      
      return response
    } catch (err) {
      setError(err.message || 'Login failed')
      throw err
    } finally {
      setLoading(false)
    }
  }

  // Register action
  const register = async (userData) => {
    try {
      setLoading(true)
      clearError()
      
      const response = await authService.register(userData)
      setAuth(response.token, response.user)
      
      return response
    } catch (err) {
      setError(err.message || 'Registration failed')
      throw err
    } finally {
      setLoading(false)
    }
  }

  // Logout action
  const logout = async () => {
    try {
      setLoading(true)
      await authService.logout()
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      clearAuth()
      setLoading(false)
    }
  }

  // Get current user data
  const fetchUser = async () => {
    try {
      setLoading(true)
      clearError()
      
      const userData = await authService.getMe()
      user.value = userData
      
      return userData
    } catch (err) {
      setError(err.message || 'Failed to fetch user data')
      throw err
    } finally {
      setLoading(false)
    }
  }

  // Refresh token
  const refreshToken = async () => {
    try {
      const newToken = await authService.refreshToken()
      token.value = newToken
      return newToken
    } catch (err) {
      clearAuth()
      throw err
    }
  }

  // Initialize auth on store creation
  initializeAuth()

  return {
    // State
    user,
    token,
    loading,
    error,
    
    // Getters
    isAuthenticated,
    currentUser,
    userFullName,
    
    // Actions
    setAuth,
    clearAuth,
    setLoading,
    setError,
    clearError,
    login,
    register,
    logout,
    fetchUser,
    refreshToken
  }
})
