import axios from 'axios'
axios.defaults.baseURL = '/api'
// axios.defaults.baseURL = 'http://localhost:8080/api' // use this if you browse at :5173

axios.interceptors.request.use((config) => {
    const t = localStorage.getItem('auth_token')
    if (t) config.headers.Authorization = `Bearer ${t}`
    return config
})

export default axios
