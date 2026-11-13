/* Otaku Track - script global (navigasi mobile, bookmark, fallback katalog). */

document.addEventListener('DOMContentLoaded', () => {
  // --- Navigasi mobile -----------------------------------------------------
  const navToggle = document.getElementById('nav-toggle');
  const navLinks = document.getElementById('primary-nav');

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      const open = document.body.classList.toggle('nav-open');
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    navLinks.addEventListener('click', (event) => {
      if (event.target.closest('a')) {
        document.body.classList.remove('nav-open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // --- Bookmark lokal ------------------------------------------------------
  document.querySelectorAll('[data-bookmark]').forEach((btn) => {
    const key = 'otaku-bookmarks';
    const id = btn.dataset.id || '';
    let saved = [];
    try {
      saved = JSON.parse(localStorage.getItem(key) || '[]');
    } catch (e) {}

    const refresh = () => {
      const active = saved.includes(id);
      btn.classList.toggle('active', active);
      btn.innerHTML = active
        ? '<i class="fa-solid fa-bookmark"></i> <span>Bookmarked</span>'
        : '<i class="fa-regular fa-bookmark"></i> <span>Bookmark</span>';
    };

    refresh();
    btn.addEventListener('click', () => {
      saved = saved.includes(id) ? saved.filter((x) => x !== id) : [...saved, id];
      localStorage.setItem(key, JSON.stringify(saved));
      refresh();
    });
  });

  // --- Fallback katalog sisi browser --------------------------------------
  // Kalau server gagal mengisi grid (API down), browser mencoba AniList lalu Jikan.
  const api = 'https://api.jikan.moe/v4';
  const ani = 'https://graphql.anilist.co';
  const esc = (x) =>
    String(x ?? '').replace(/[&<>'"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));
  const titleOf = (a) => a?.title?.english || a?.title?.romaji || a?.title?.native || a?.title || 'Untitled';
  const imgOf = (a) =>
    a?.coverImage?.extraLarge || a?.coverImage?.large || a?.images?.jpg?.large_image_url || a?.images?.jpg?.image_url || '';
  const detailHref = (a, type = 'anime') => {
    const params = new URLSearchParams({ type });
    if (a?.idMal || a?.mal_id) params.set('mal_id', String(a.idMal || a.mal_id));
    if (a?.anilist_id || a?.id) params.set('anilist_id', String(a.anilist_id || a.id));
    return `/detail?${params.toString()}`;
  };
  const yearOf = (a) => a?.seasonYear || a?.year || '';
  const card = (a, type = 'anime') =>
    `<a class="media-card" href="${detailHref(a, type)}"><div class="poster"><img src="${esc(imgOf(a))}" alt="${esc(titleOf(a))}" loading="lazy"><span class="score"><i class="fa-solid fa-star"></i> ${esc(a?.score ?? (a?.averageScore ? (a.averageScore / 10).toFixed(1) : 'N/A'))}</span></div><div class="media-title">${esc(titleOf(a))}</div><div class="media-meta">${esc(a?.type || a?.format || type)} &middot; ${esc(yearOf(a))}</div></a>`;

  async function json(url, options = {}) {
    const ctrl = new AbortController();
    const t = setTimeout(() => ctrl.abort(), 10000);
    try {
      const r = await fetch(url, { ...options, signal: ctrl.signal });
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return await r.json();
    } finally {
      clearTimeout(t);
    }
  }

  async function aniReq(query, variables) {
    const r = await json(ani, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ query, variables }),
    });
    if (r.errors?.length) throw new Error(r.errors[0].message);
    return r.data;
  }

  const browseQuery = `query($type:MediaType,$sort:[MediaSort],$page:Int,$perPage:Int){Page(page:$page,perPage:$perPage){media(type:$type,isAdult:false,sort:$sort){idMal id format title{romaji english native} coverImage{large extraLarge} averageScore seasonYear}}}`;
  async function getBrowse(type, sort = 'POPULARITY_DESC', page = 1) {
    const d = await aniReq(browseQuery, { type, sort: [sort], page, perPage: 28 });
    return d.Page?.media || [];
  }

  const search = document.querySelector('[data-search-page]');
  if (search) {
    const q = search.dataset.q || '';
    const type = search.dataset.type === 'manga' ? 'manga' : 'anime';
    const page = parseInt(search.dataset.page || '1', 10);
    const grid = document.getElementById('search-results-grid');
    const loading = document.getElementById('search-loading');
    const count = document.getElementById('search-result-count');
    const label = document.getElementById('catalog-label');
    const already = grid?.querySelectorAll('.media-card').length || 0;

    if (grid && already === 0) {
      (async () => {
        try {
          let data = [];
          if (q) {
            const query = `query($type:MediaType,$search:String,$page:Int,$perPage:Int){Page(page:$page,perPage:$perPage){media(type:$type,search:$search,isAdult:false,sort:SEARCH_MATCH){idMal id format title{romaji english native} coverImage{large extraLarge} averageScore seasonYear}}}`;
            const d = await aniReq(query, { type: type.toUpperCase(), search: q, page, perPage: 28 });
            data = d.Page?.media || [];
          } else {
            data = await getBrowse(
              type,
              new URLSearchParams(location.search).get('order_by') === 'score' ? 'SCORE_DESC' : 'POPULARITY_DESC',
              page
            );
          }

          if (data.length) {
            grid.innerHTML = data.map((a) => card(a, type)).join('');
            count && (count.textContent = data.length);
            label && (label.textContent = data.length);
            loading?.remove();
            return;
          }
        } catch (e) {}

        try {
          const ep = type === 'manga' ? 'manga' : 'anime';
          const u = q
            ? `${api}/${ep}?q=${encodeURIComponent(q)}&page=${page}&limit=25&sfw=true`
            : `${api}/top/${ep}?page=${page}&filter=bypopularity&limit=25&sfw=true`;
          const d = await json(u);
          if (d.data?.length) {
            grid.innerHTML = d.data.map((a) => card(a, type)).join('');
            count && (count.textContent = d.data.length);
            label && (label.textContent = d.data.length);
            loading?.remove();
          }
        } catch (e) {
          if (loading) loading.textContent = 'API sedang tidak tersedia. Coba refresh beberapa detik lagi.';
        }
      })();
    }
  }
});

// Konfirmasi sebelum aksi yang menghapus data: <form data-confirm="Pesan konfirmasi">
document.addEventListener('submit', (event) => {
  const form = event.target.closest('form[data-confirm]');
  if (form && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
  }
});
