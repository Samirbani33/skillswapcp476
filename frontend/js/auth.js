// auth.js — Register and Login logic
document.addEventListener('DOMContentLoaded', () => {
  updateNav();

  // REGISTER
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearFieldErrors('errFirst','errLast','errEmail','errPassword','errRole');
      hideAlert('formAlert');

      const firstName = document.getElementById('firstName').value;
      const lastName  = document.getElementById('lastName').value;
      const email     = document.getElementById('email').value;
      const password  = document.getElementById('password').value;
      const role      = document.getElementById('role').value;

      let valid = true;
      if (!firstName.trim()) { showFieldError('errFirst', 'First name is required.'); valid = false; }
      if (!lastName.trim())  { showFieldError('errLast',  'Last name is required.');  valid = false; }
      if (!isEmail(email))   { showFieldError('errEmail', 'Enter a valid email.');    valid = false; }
      if (!isStrongPassword(password)) { showFieldError('errPassword', 'Min. 8 characters.'); valid = false; }
      if (!role)             { showFieldError('errRole',  'Please select a role.');   valid = false; }
      if (!valid) return;

      const btn = registerForm.querySelector('button[type=submit]');
      btn.textContent = 'Registering...'; btn.disabled = true;

      const { ok, data } = await apiFetch('/auth/register', {
        method: 'POST',
        body: JSON.stringify({ firstName, lastName, email, password, role })
      });

      if (ok) {
        setUser(data.user);
        showAlert('formAlert', 'Account created! Redirecting...', 'success');
        setTimeout(() => window.location.href = role === 'provider' ? 'provider-dashboard.html' : 'marketplace.html', 1000);
      } else {
        showAlert('formAlert', data.message || 'Registration failed.', 'error');
        btn.textContent = 'Create Account'; btn.disabled = false;
      }
    });
  }

  // LOGIN
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      hideAlert('formAlert');

      const email    = document.getElementById('email').value;
      const password = document.getElementById('password').value;

      if (!email || !password) { showAlert('formAlert', 'Please fill in all fields.'); return; }

      const btn = loginForm.querySelector('button[type=submit]');
      btn.textContent = 'Logging in...'; btn.disabled = true;

      const { ok, data } = await apiFetch('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password })
      });

      if (ok) {
        setUser(data.user);
        const role = data.user.role;
        window.location.href = role === 'provider' ? 'provider-dashboard.html'
                             : role === 'admin'    ? 'admin-dashboard.html'
                             : 'marketplace.html';
      } else {
        showAlert('formAlert', data.message || 'Login failed.', 'error');
        btn.textContent = 'Login'; btn.disabled = false;
      }
    });
  }
});
