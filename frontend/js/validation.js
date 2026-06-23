// validation.js — Client-side validation helpers
function isEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); }
function isStrongPassword(v) { return v.length >= 8; }

function showFieldError(id, msg) {
  const el = document.getElementById(id);
  if (el) { el.textContent = msg; el.style.display = 'block'; }
}
function clearFieldErrors(...ids) {
  ids.forEach(id => { const el = document.getElementById(id); if (el) { el.textContent = ''; el.style.display = 'none'; } });
}
function showAlert(id, msg, type = 'error') {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.className = `alert alert-${type}`;
  el.style.display = 'block';
}
function hideAlert(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}
