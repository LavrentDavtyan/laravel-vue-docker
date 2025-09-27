// resources/js/http.js
import axios from 'axios';

axios.defaults.baseURL = '/api';

// read token saved by your login flow
const token = localStorage.getItem('auth_token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// expose a helper so login code can set the header right after login
window.setAuthToken = (t) => {
    if (!t) return;
    localStorage.setItem('auth_token', t);
    axios.defaults.headers.common['Authorization'] = `Bearer ${t}`;
};

export default axios;
