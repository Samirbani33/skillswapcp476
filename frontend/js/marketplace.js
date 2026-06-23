// marketplace.js — Service browsing and searching
document.addEventListener('DOMContentLoaded', () => {
  updateNav();
  loadServices();

  document.getElementById('searchBtn')?.addEventListener('click', loadServices);
  document.getElementById('searchInput')?.addEventListener('keydown', e => { if (e.key === 'Enter') loadServices(); });
  document.getElementById('applyFilters')?.addEventListener('click', loadServices);
});

async function loadServices() {
  const grid     = document.getElementById('servicesGrid');
  const query    = document.getElementById('searchInput')?.value   || '';
  const category = document.getElementById('categoryFilter')?.value || '';
  const maxPrice = document.getElementById('priceFilter')?.value    || '';
  const sort     = document.getElementById('sortFilter')?.value     || 'rating';

  grid.innerHTML = '<p class="loading">Loading services...</p>';

  const { ok, data } = await apiFetch(`/services?query=${encodeURIComponent(query)}&category=${category}&maxPrice=${maxPrice}&sort=${sort}`);

  if (!ok || !data.length) {
    grid.innerHTML = '<p class="empty-msg">No services found. Try a different search or category.</p>';
    return;
  }

  grid.innerHTML = data.map(s => `
    <div class="service-card" onclick="window.location='service-detail.html?id=${s.service_id}'">
      <div class="card-cat">${s.category}</div>
      <div class="card-title">${s.title}</div>
      <div class="card-provider">by ${s.provider_name}</div>
      <div class="card-rating">${renderStars(s.avg_rating)} (${s.review_count})</div>
      <div class="card-price">$${parseFloat(s.price).toFixed(2)}</div>
    </div>
  `).join('');
}

function renderStars(rating) {
  const r = Math.round(parseFloat(rating));
  return '★'.repeat(r) + '☆'.repeat(5 - r);
}
