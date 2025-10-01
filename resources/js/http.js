import axios from 'axios'
import router from './router'

// Base URL (keep '/api' if your app and API are served from :8080 together)
axios.defaults.baseURL = '/api'

// Attach token on every request
axios.interceptors.request.use((config) => {
    const t = localStorage.getItem('auth_token')
    if (t) config.headers.Authorization = `Bearer ${t}`
    return config
})

// Catch 401 globally and redirect to login
axios.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err?.response?.status === 401) {
            try {
                localStorage.removeItem('auth_token')
                localStorage.removeItem('user')
            } catch (_) {}
            if (router.currentRoute?.value?.path !== '/login') {
                router.push('/login')
            }
        }
        return Promise.reject(err)
    }
)

export default axios
