// api.js — Central API helper and nav updater
const API = 'http://localhost:8000';

async function apiFetch(path, options = {}) {
  try {
    const res = await fetch(`${API}${path}`, {
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      ...options
    });
    const data = await res.json();
    return { ok: res.ok, status: res.status, data };
  } catch (err) {
    console.error('API error:', err);
    return { ok: false, status: 0, data: { message: 'Cannot connect to server. Is the backend running?' } };
  }
}

function getUser() {
  try { return JSON.parse(localStorage.getItem('ss_user')); } catch { return null; }
}
function setUser(u) { localStorage.setItem('ss_user', JSON.stringify(u)); }
function clearUser() { localStorage.removeItem('ss_user'); }

function updateNav() {
  const user     = getUser();
  const navLinks = document.getElementById('navLinks');
  if (!navLinks) return;

  if (user) {
    const dashLink = user.role === 'provider' ? 'provider-dashboard.html'
                   : user.role === 'admin'    ? 'admin-dashboard.html'
                   : 'customer-dashboard.html';
    navLinks.innerHTML = `
      <a href="marketplace.html">Browse</a>
      <a href="messages.html">Messages</a>
      <a href="${dashLink}">Dashboard</a>
      <span class="nav-user" style="color:var(--muted);font-size:.85rem">Hi, ${user.first_name}</span>
      <a href="#" id="logoutBtn" class="btn btn-secondary" style="padding:.4rem 1rem;font-size:.85rem">Logout</a>`;
    document.getElementById('logoutBtn')?.addEventListener('click', async (e) => {
      e.preventDefault();
      await apiFetch('/auth/logout', { method: 'POST' });
      clearUser();
      window.location.href = 'index.html';
    });
  } else {
    navLinks.innerHTML = `
      <a href="marketplace.html">Browse</a>
      <a href="login.html">Login</a>
      <a href="register.html" class="btn btn-primary">Register</a>`;
  }
}
