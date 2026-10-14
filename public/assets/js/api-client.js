/**
 * Match Quest API Client
 * Fetch wrapper with JWT, retry, offline queue, config caching
 */

class ApiClient {
  constructor() {
    this.base = window.API_BASE
      || document.querySelector('meta[name="api-base"]')?.content
      || '/3Row/api/v1';
    this.token = localStorage.getItem('mq_token');
    this.offlineQueue = [];
    this.isOnline = navigator.onLine;
    window.addEventListener('online', () => this.flushOfflineQueue());
    window.addEventListener('offline', () => { this.isOnline = false; });
  }

  async request(method, endpoint, data = null, options = {}) {
    const url = `${this.base}${endpoint}`;
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Config-Version': localStorage.getItem('mq_config_version') || '0',
    };
    if (this.token) headers['Authorization'] = `Bearer ${this.token}`;

    const config = {
      method,
      headers,
      ...options,
    };
    if (data) config.body = JSON.stringify(data);

    // Offline queue for non-GET requests
    if (!this.isOnline && method !== 'GET') {
      this.offlineQueue.push({ method, endpoint, data });
      throw new Error('Offline — запрос поставлен в очередь');
    }

    try {
      const res = await fetch(url, config);

      // 304 Not Modified for config
      if (res.status === 304) return { unchanged: true };

      if (res.status === 401) {
        if (this.token) {
          // Token present but rejected — session expired or revoked
          this.token = null;
          localStorage.removeItem('mq_token');
          window.dispatchEvent(new CustomEvent('auth:logout'));
          throw new Error('Сессия истекла');
        }
        // Guest request to a protected route — no logout redirect
        throw new Error('Требуется авторизация');
      }

      const json = await res.json();
      if (!res.ok) throw new Error(json.error || `HTTP ${res.status}`);
      return json;
    } catch (err) {
      if (err.message.includes('fetch')) {
        this.isOnline = false;
        if (method !== 'GET') this.offlineQueue.push({ method, endpoint, data });
      }
      throw err;
    }
  }

  get(endpoint) { return this.request('GET', endpoint); }
  post(endpoint, data) { return this.request('POST', endpoint, data); }

  async flushOfflineQueue() {
    this.isOnline = true;
    while (this.offlineQueue.length > 0) {
      const req = this.offlineQueue.shift();
      try { await this.request(req.method, req.endpoint, req.data); }
      catch (e) { this.offlineQueue.unshift(req); break; }
    }
  }

  // Config caching
  async loadConfig() {
    const cached = localStorage.getItem('mq_config');
    const cachedVersion = cached ? JSON.parse(cached).version : 0;
    try {
      const config = await this.get('/config');
      if (!config.unchanged) {
        localStorage.setItem('mq_config', JSON.stringify(config));
        localStorage.setItem('mq_config_version', config.version);
      }
      return config.unchanged ? JSON.parse(cached) : config;
    } catch (e) {
      return cached ? JSON.parse(cached) : null;
    }
  }
}

window.api = new ApiClient();
