// Authentication service for API calls
const API_BASE_URL = '/api';

class AuthService {
    constructor() {
        this.token = localStorage.getItem('auth_token');
        this.user = JSON.parse(localStorage.getItem('user') || 'null');
    }

    // Set authentication data
    setAuth(token, user) {
        this.token = token;
        this.user = user;
        localStorage.setItem('auth_token', token);
        localStorage.setItem('user', JSON.stringify(user));
    }

    // Clear authentication data
    clearAuth() {
        this.token = null;
        this.user = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
    }

    // Check if user is authenticated
    isAuthenticated() {
        return !!this.token;
    }

    // Get current user
    getCurrentUser() {
        return this.user;
    }

    // Get auth token
    getToken() {
        return this.token;
    }

    // Make authenticated API request
    async apiRequest(url, options = {}) {
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            },
            ...options
        };

        if (this.token) {
            config.headers.Authorization = `Bearer ${this.token}`;
        }

        const response = await fetch(`${API_BASE_URL}${url}`, config);
        
        if (response.status === 401) {
            this.clearAuth();
            window.location.href = '/login';
            throw new Error('Unauthorized');
        }

        return response;
    }

    // Register new user
    async register(userData) {
        try {
            const response = await this.apiRequest('/register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Registration failed');
            }

            const data = await response.json();
            this.setAuth(data.token, data.user);
            return data;
        } catch (error) {
            throw error;
        }
    }

    // Login user
    async login(credentials) {
        try {
            const response = await this.apiRequest('/login', {
                method: 'POST',
                body: JSON.stringify(credentials)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Login failed');
            }

            const data = await response.json();
            this.setAuth(data.token, data.user);
            return data;
        } catch (error) {
            throw error;
        }
    }

    // Logout user
    async logout() {
        try {
            if (this.token) {
                await this.apiRequest('/logout', {
                    method: 'POST'
                });
            }
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.clearAuth();
        }
    }

    // Get current user data
    async getMe() {
        try {
            const response = await this.apiRequest('/me');
            
            if (!response.ok) {
                throw new Error('Failed to get user data');
            }

            const data = await response.json();
            this.user = data.user;
            localStorage.setItem('user', JSON.stringify(data.user));
            return data.user;
        } catch (error) {
            throw error;
        }
    }

    // Refresh token
    async refreshToken() {
        try {
            const response = await this.apiRequest('/refresh', {
                method: 'POST'
            });

            if (!response.ok) {
                throw new Error('Token refresh failed');
            }

            const data = await response.json();
            this.token = data.token;
            localStorage.setItem('auth_token', data.token);
            return data.token;
        } catch (error) {
            this.clearAuth();
            throw error;
        }
    }
}

// Create and export singleton instance
export const authService = new AuthService();
export default authService;
