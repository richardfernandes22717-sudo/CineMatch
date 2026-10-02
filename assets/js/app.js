const $ = (selector, parent = document) => parent.querySelector(selector);
const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];
const FALLBACK_POSTER = 'assets/img/fallback-poster.svg';

const state = {
    page: 1,
    mood: 'top',
    view: 'discover',
    hasMore: false,
    loading: false,
    favourites: new Set(JSON.parse(localStorage.getItem('movieverse-favourites') || '[]').map(Number)),
};

const elements = {
    search: $('#searchInput'),
    genre: $('#genreFilter'),
    decade: $('#decadeFilter'),
    rating: $('#ratingFilter'),
    sort: $('#sortFilter'),
    grid: $('#movieGrid'),
    recommendationGrid: $('#recommendationGrid'),
    resultsCount: $('#resultsCount'),
    loadMore: $('#loadMoreButton'),
    empty: $('#emptyState'),
    catalogueTitle: $('#catalogueTitle'),
    favouriteCount: $('#favouriteCount'),
    featuredCard: $('#featuredCard'),
    toast: $('#toast'),
};

function escapeHtml(value = '') {
    return String(value).replace(/[&<>'"]/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    })[character]);
}

function formatCount(value) {
    return new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(Number(value || 0));
}

async function fetchJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Unable to load movies.');
    }
    return data;
}

function posterMarkup(movie, extraClass = '') {
    return `<img class="${extraClass}" src="${escapeHtml(movie.poster_url || FALLBACK_POSTER)}" alt="${escapeHtml(movie.title)} poster" loading="lazy" onerror="this.onerror=null;this.src='${FALLBACK_POSTER}'">`;
}

function movieCard(movie, compact = false) {
    const favourite = state.favourites.has(Number(movie.id));
    const primaryGenre = String(movie.genre || 'Drama').split(',')[0];
    return `
        <article class="movie-card ${compact ? 'compact-card' : ''}" data-id="${movie.id}">
            <a class="poster-wrap" href="movie.php?id=${movie.id}" aria-label="View ${escapeHtml(movie.title)}">
                ${posterMarkup(movie)}
                <span class="rating-badge">★ ${Number(movie.imdb_rating).toFixed(1)}</span>
                <span class="poster-shine"></span>
            </a>
            <button class="favourite-button ${favourite ? 'is-saved' : ''}" data-favourite="${movie.id}" aria-label="${favourite ? 'Remove from' : 'Add to'} favourites">${favourite ? '♥' : '♡'}</button>
            <div class="movie-card-body">
                <a href="movie.php?id=${movie.id}" class="movie-title">${escapeHtml(movie.title)}</a>
                <div class="movie-meta"><span>${escapeHtml(movie.release_year)}</span><i></i><span>${escapeHtml(primaryGenre)}</span></div>
            </div>
        </article>`;
}

function skeletonCards(count = 8) {
    return Array.from({ length: count }, () => '<div class="movie-card skeleton-card"><div></div><span></span><small></small></div>').join('');
}

function queryString(page = 1) {
    const params = new URLSearchParams({
        page: String(page),
        limit: '20',
        search: elements.search.value.trim(),
        genre: elements.genre.value,
        decade: elements.decade.value,
        min_rating: elements.rating.value,
        sort: elements.sort.value,
    });
    return params.toString();
}

async function loadMovies({ append = false } = {}) {
    if (state.loading) return;
    state.loading = true;
    elements.empty.classList.add('hidden');
    if (!append) elements.grid.innerHTML = skeletonCards(10);

    try {
        if (state.view === 'favourites') {
            await loadFavourites();
            return;
        }

        const data = await fetchJson(`api/movies.php?${queryString(state.page)}`);
        const cards = data.movies.map(movie => movieCard(movie)).join('');
        elements.grid.innerHTML = append ? elements.grid.innerHTML + cards : cards;
        state.hasMore = data.pagination.has_more;
        elements.loadMore.classList.toggle('hidden', !state.hasMore);
        elements.resultsCount.textContent = `${data.pagination.total} movie${data.pagination.total === 1 ? '' : 's'}`;
        elements.empty.classList.toggle('hidden', data.pagination.total !== 0);
    } catch (error) {
        showSetupError(error.message);
    } finally {
        state.loading = false;
    }
}

async function loadRecommendations() {
    elements.recommendationGrid.innerHTML = skeletonCards(6);
    try {
        const params = new URLSearchParams({ mood: state.mood, genre: elements.genre.value, limit: '12' });
        const data = await fetchJson(`api/recommend.php?${params}`);
        elements.recommendationGrid.innerHTML = data.movies.map(movie => movieCard(movie, true)).join('');
        if (data.movies[0]) renderFeatured(data.movies[0]);
    } catch (error) {
        elements.recommendationGrid.innerHTML = `<div class="inline-error">${escapeHtml(error.message)} <a href="install.php">Run setup</a></div>`;
    }
}

async function loadFavourites() {
    elements.catalogueTitle.textContent = 'My saved movies';
    elements.loadMore.classList.add('hidden');
    if (state.favourites.size === 0) {
        elements.grid.innerHTML = '';
        elements.resultsCount.textContent = '0 movies';
        elements.empty.classList.remove('hidden');
        $('.empty-state h3').textContent = 'Your list is empty';
        $('.empty-state p').textContent = 'Tap the heart on any movie to save it here.';
        return;
    }

    const data = await fetchJson('api/movies.php?limit=40&sort=rank');
    let allMovies = [...data.movies];
    let current = 2;
    while (data.pagination?.pages >= current && allMovies.length < 250) {
        const next = await fetchJson(`api/movies.php?limit=40&sort=rank&page=${current}`);
        allMovies = allMovies.concat(next.movies);
        current++;
    }
    const favourites = allMovies.filter(movie => state.favourites.has(Number(movie.id)));
    elements.grid.innerHTML = favourites.map(movie => movieCard(movie)).join('');
    elements.resultsCount.textContent = `${favourites.length} saved`;
    elements.empty.classList.add('hidden');
}

function renderFeatured(movie) {
    const primaryGenre = String(movie.genre || 'Drama').split(',')[0];
    elements.featuredCard.innerHTML = `
        <div class="featured-poster">${posterMarkup(movie)}</div>
        <div class="featured-overlay"></div>
        <div class="featured-content">
            <div class="featured-label">Tonight's standout</div>
            <h2>${escapeHtml(movie.title)}</h2>
            <div class="featured-meta"><span>★ ${Number(movie.imdb_rating).toFixed(1)}</span><span>${escapeHtml(movie.release_year)}</span><span>${escapeHtml(primaryGenre)}</span></div>
            <p>${escapeHtml(String(movie.description || '').slice(0, 160))}${String(movie.description || '').length > 160 ? '…' : ''}</p>
            <a class="featured-link" href="movie.php?id=${movie.id}">View details →</a>
        </div>`;
}

function toggleFavourite(id) {
    const numericId = Number(id);
    if (state.favourites.has(numericId)) {
        state.favourites.delete(numericId);
        showToast('Removed from My List');
    } else {
        state.favourites.add(numericId);
        showToast('Added to My List');
    }
    localStorage.setItem('movieverse-favourites', JSON.stringify([...state.favourites]));
    updateFavouriteCount();
    $$(`[data-favourite="${numericId}"]`).forEach(button => {
        const saved = state.favourites.has(numericId);
        button.classList.toggle('is-saved', saved);
        button.textContent = saved ? '♥' : '♡';
    });
    if (state.view === 'favourites') loadMovies();
}

function updateFavouriteCount() {
    elements.favouriteCount.textContent = state.favourites.size;
}

function showToast(message) {
    elements.toast.textContent = message;
    elements.toast.classList.add('show');
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => elements.toast.classList.remove('show'), 1800);
}

function showSetupError(message) {
    elements.grid.innerHTML = `<div class="setup-error"><span>Database setup needed</span><h3>${escapeHtml(message)}</h3><a class="primary-button" href="install.php">Open installer</a></div>`;
    elements.resultsCount.textContent = 'Not connected';
    elements.loadMore.classList.add('hidden');
}

function resetAndLoad() {
    state.page = 1;
    state.view = 'discover';
    $$('.nav-tab').forEach(tab => tab.classList.toggle('is-active', tab.dataset.view === 'discover'));
    elements.catalogueTitle.textContent = 'Explore all movies';
    $('.empty-state h3').textContent = 'No movies found';
    $('.empty-state p').textContent = 'Try removing one filter or searching for another title.';
    loadMovies();
    loadRecommendations();
}

let searchTimer;
elements.search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(resetAndLoad, 320);
});
[elements.genre, elements.decade, elements.rating, elements.sort].forEach(control => control.addEventListener('change', resetAndLoad));

elements.loadMore.addEventListener('click', () => {
    state.page++;
    loadMovies({ append: true });
});

$('#refreshRecommendations').addEventListener('click', loadRecommendations);
$('#surpriseButton').addEventListener('click', async () => {
    try {
        const data = await fetchJson('api/recommend.php?mood=top&limit=1');
        if (data.movies[0]) window.location.href = `movie.php?id=${data.movies[0].id}`;
    } catch (error) {
        showToast(error.message);
    }
});

$('#clearFiltersButton').addEventListener('click', () => {
    elements.search.value = '';
    elements.genre.value = '';
    elements.decade.value = '';
    elements.rating.value = '0';
    elements.sort.value = 'rank';
    resetAndLoad();
});

$('#moodRow').addEventListener('click', event => {
    const button = event.target.closest('[data-mood]');
    if (!button) return;
    state.mood = button.dataset.mood;
    $$('.mood-chip').forEach(chip => chip.classList.toggle('is-active', chip === button));
    loadRecommendations();
});

document.addEventListener('click', event => {
    const favouriteButton = event.target.closest('[data-favourite]');
    if (favouriteButton) {
        event.preventDefault();
        toggleFavourite(favouriteButton.dataset.favourite);
    }
    const scrollButton = event.target.closest('[data-scroll]');
    if (scrollButton) $(scrollButton.dataset.scroll)?.scrollIntoView({ behavior: 'smooth' });
});

$$('.nav-tab').forEach(tab => tab.addEventListener('click', () => {
    state.view = tab.dataset.view;
    $$('.nav-tab').forEach(item => item.classList.toggle('is-active', item === tab));
    if (state.view === 'favourites') {
        loadMovies();
        $('#catalogue').scrollIntoView({ behavior: 'smooth' });
    } else if (state.view === 'all') {
        state.view = 'discover';
        $('#catalogue').scrollIntoView({ behavior: 'smooth' });
    } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}));

document.addEventListener('keydown', event => {
    if (event.key === '/' && document.activeElement !== elements.search) {
        event.preventDefault();
        elements.search.focus();
    }
});

updateFavouriteCount();
loadRecommendations();
loadMovies();
