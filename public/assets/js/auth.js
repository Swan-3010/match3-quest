/**
 * Match Quest Auth Module
 * JWT management, auto-refresh, guest mode
 */

class AuthManager {
  constructor() {
    this.token = localStorage.getItem('mq_token');
    this.user = JSON.parse(localStorage.getItem('mq_user') || 'null');
    this.isGuest = !this.token;
  }

  setToken(token, user = null) {
    this.token = token;
    this.isGuest = false;
    localStorage.setItem('mq_token', token);
    if (user) {
      this.user = user;
      localStorage.setItem('mq_user', JSON.stringify(user));
    }
    if (window.api) window.api.token = token;
  }

  clear() {
    this.token = null;
    this.user = null;
    this.isGuest = true;
    localStorage.removeItem('mq_token');
    localStorage.removeItem('mq_user');
    localStorage.removeItem('mq_config');
    if (window.api) window.api.token = null;
  }

  async login(email, password) {
    const res = await fetch(`${window.API_BASE}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Ошибка входа');
    this.setToken(data.token, data.user);
    return data;
  }

  async register(email, password, passwordConfirmation) {
    const res = await fetch(`${window.API_BASE}/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email, password, password_confirmation: passwordConfirmation }),
    });
    const data = await res.json();
    console.log('register response:', res.status, data);
    if (!res.ok) throw new Error(data.message || JSON.stringify(data.errors) || 'Ошибка регистрации');
    this.setToken(data.token, data.user);
    return data;
  }

  logout() {
    if (!this.isGuest) {
      fetch(`${window.API_BASE}/auth/logout`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${this.token}` },
      }).catch(() => {});
    }
    this.clear();
    window.dispatchEvent(new CustomEvent('auth:logout'));
  }

  isAuthenticated() {
    return !!this.token;
  }
}

window.auth = new AuthManager();

// Auto-logout on 401
window.addEventListener('auth:logout', () => {
  window.location.href = new URL(
    'login',
    document.querySelector('meta[name="app-base"]').content
  ).href;
});
