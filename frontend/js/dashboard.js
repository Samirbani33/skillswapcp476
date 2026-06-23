// dashboard.js — Shared dashboard logic for provider and customer
document.addEventListener('DOMContentLoaded', async () => {
  updateNav();
  const user = getUser();
  if (!user) { window.location.href = 'login.html'; return; }

  // Tab switching
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(tab.dataset.target)?.classList.add('active');
    });
  });

  await loadBookings();

  // Provider: also load their services
  if (user.role === 'provider') await loadMyServices();
});

async function loadBookings() {
  const user = getUser();
  const { ok, data } = await apiFetch('/bookings');
  const container = document.getElementById('bookingsTable');
  if (!container) return;

  if (!ok) { container.innerHTML = '<p class="text-muted">Could not load bookings.</p>'; return; }
  if (!data.length) { container.innerHTML = '<p class="text-muted mt-2">No bookings yet.</p>'; return; }

  // Update stat cards
  const counts = { Pending: 0, Active: 0, Completed: 0 };
  data.forEach(b => { if (counts[b.booking_status] !== undefined) counts[b.booking_status]++; });

  ['Pending','Active','Completed'].forEach(k => {
    const el = document.getElementById(`stat${k}`);
    if (el) el.querySelector('.num').textContent = counts[k];
  });

  const nameCol = user.role === 'provider' ? 'customer_name' : 'provider_name';
  const nameHdr = user.role === 'provider' ? 'Customer' : 'Provider';

  const rows = data.map(b => `
    <tr>
      <td><strong>${b.service_title}</strong></td>
      <td>${b[nameCol] || '—'}</td>
      <td><span class="badge badge-${b.booking_status.toLowerCase()}">${b.booking_status}</span></td>
      <td>${new Date(b.booking_date).toLocaleDateString()}</td>
      <td style="white-space:nowrap">
        ${user.role === 'provider' && b.booking_status === 'Pending' ? `
          <button class="btn btn-success btn-sm" onclick="updateBooking(${b.booking_id},'Active')">Accept</button>
          <button class="btn btn-danger btn-sm"  onclick="updateBooking(${b.booking_id},'Rejected')">Reject</button>` : ''}
        ${b.booking_status === 'Active' ? `
          <button class="btn btn-primary btn-sm" onclick="updateBooking(${b.booking_id},'Completed')">Mark Complete</button>` : ''}
        ${b.booking_status === 'Completed' && user.role === 'customer' ? `
          <button class="btn btn-secondary btn-sm" onclick="openReviewModal(${b.booking_id})">Leave Review</button>` : ''}
        ${b.booking_status === 'Pending' && user.role === 'customer' ? `
          <button class="btn btn-danger btn-sm" onclick="updateBooking(${b.booking_id},'Cancelled')">Cancel</button>` : ''}
      </td>
    </tr>`).join('');

  container.innerHTML = `
    <table class="data-table">
      <thead>
        <tr><th>Service</th><th>${nameHdr}</th><th>Status</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>`;
}

async function loadMyServices() {
  const container = document.getElementById('servicesTable');
  if (!container) return;

  const { ok, data } = await apiFetch('/services?query=&sort=newest');
  const user = getUser();
  const mine = ok ? data.filter(s => s.provider_id == user.user_id) : [];

  if (!mine.length) { container.innerHTML = '<p class="text-muted mt-2">No services yet. Create one in the "New Service" tab.</p>'; return; }

  const rows = mine.map(s => `
    <tr>
      <td><strong>${s.title}</strong></td>
      <td>${s.category}</td>
      <td>$${parseFloat(s.price).toFixed(2)}</td>
      <td style="color:#F59E0B">${'★'.repeat(Math.round(s.avg_rating))} (${s.review_count})</td>
      <td>
        <a href="service-detail.html?id=${s.service_id}" class="btn btn-secondary btn-sm">View</a>
        <button class="btn btn-danger btn-sm" onclick="deleteService(${s.service_id})">Remove</button>
      </td>
    </tr>`).join('');

  container.innerHTML = `
    <table class="data-table">
      <thead><tr><th>Title</th><th>Category</th><th>Price</th><th>Rating</th><th>Actions</th></tr></thead>
      <tbody>${rows}</tbody>
    </table>`;
}

async function updateBooking(id, status) {
  const { ok, data } = await apiFetch(`/bookings/${id}`, {
    method: 'PUT', body: JSON.stringify({ booking_status: status })
  });
  if (ok) {
    showAlert('dashAlert', `Booking ${status.toLowerCase()} successfully.`, 'success');
    await loadBookings();
  } else {
    showAlert('dashAlert', data.message || 'Update failed.');
  }
}

async function deleteService(id) {
  if (!confirm('Remove this service from the marketplace?')) return;
  const { ok } = await apiFetch(`/services/${id}`, { method: 'DELETE' });
  if (ok) { showAlert('dashAlert', 'Service removed.', 'success'); await loadMyServices(); }
  else showAlert('dashAlert', 'Could not remove service.');
}

function openReviewModal(bookingId) {
  document.getElementById('reviewBookingId').value = bookingId;
  document.getElementById('reviewModal').style.display = 'flex';
}
