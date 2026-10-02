const root = document.querySelector('#detailsRoot');
const toast = document.querySelector('#toast');
const FALLBACK_POSTER = 'assets/img/fallback-poster.svg';
const params = new URLSearchParams(window.location.search);
const id = Number(params.get('id'));
let favourites = new Set(JSON.parse(localStorage.getItem('movieverse-favourites') || '[]').map(Number));

function escapeHtml(value = '') {
    return String(value).replace(/[&<>'"]/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    })[character]);
}

function showToast(message) {
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => toast.classList.remove('show'), 1800);
}

function toggleFavourite(movieId) {
    if (favourites.has(movieId)) {
        favourites.delete(movieId);
        showToast('Removed from My List');
    } else {
        favourites.add(movieId);
        showToast('Added to My List');
    }
    localStorage.setItem('movieverse-favourites', JSON.stringify([...favourites]));
    const button = document.querySelector('#detailFavourite');
    if (button) {
        button.classList.toggle('is-saved', favourites.has(movieId));
        button.innerHTML = favourites.has(movieId) ? '♥ Saved to My List' : '♡ Add to My List';
    }
}


function relatedCard(movie) {
    const rating = Number(movie.imdb_rating);
    const ratingText = Number.isFinite(rating)
        ? rating.toFixed(1)
        : 'N/A';

    return `<a class="related-card" href="movie.php?id=${Number(movie.id)}">
        <img
            src="${escapeHtml(movie.poster_url || FALLBACK_POSTER)}"
            alt="${escapeHtml(movie.title || 'Movie')} poster"
            onerror="this.onerror=null;this.src='${FALLBACK_POSTER}'"
        >
        <div>
            <strong>${escapeHtml(movie.title || 'Untitled movie')}</strong>
            <span>★ ${ratingText} · ${escapeHtml(movie.release_year ?? 'Year unavailable')}</span>
        </div>
    </a>`;
}


async function loadMovie() {
    if (!Number.isInteger(id) || id < 1) {
        root.innerHTML = '<div class="setup-error"><h3>Invalid movie link.</h3><a class="primary-button" href="index.php">Back home</a></div>';
        return;
    }

    try {
        const response = await fetch(`api/movie.php?id=${id}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Movie not found.');
        renderMovie(data.movie, data.related);
    } catch (error) {
        root.innerHTML = `<div class="setup-error"><span>Could not load this movie</span><h3>${escapeHtml(error.message)}</h3><a class="primary-button" href="index.php">Back home</a></div>`;
    }
}

function renderMovie(movie, related) {
    document.title = `${movie.title} — CineMatch`;
    const saved = favourites.has(Number(movie.id));
    const genres = String(movie.genre || 'Drama').split(',').map(item => `<span>${escapeHtml(item.trim())}</span>`).join('');
    const runtime = movie.runtime ? `${movie.runtime} min` : 'Runtime unavailable';
    const imdbUrl = `https://www.imdb.com/title/${encodeURIComponent(movie.imdb_id)}/`;

    root.innerHTML = `
        <section class="details-hero">
            <div class="details-backdrop" style="background-image:linear-gradient(90deg,#09090d 4%,rgba(9,9,13,.8) 48%,rgba(9,9,13,.15)),url('${escapeHtml(movie.poster_url)}')"></div>
            <div class="details-poster-wrap">
                <img class="details-poster" src="${escapeHtml(movie.poster_url || FALLBACK_POSTER)}" alt="${escapeHtml(movie.title)} poster" onerror="this.onerror=null;this.src='${FALLBACK_POSTER}'">
                <span class="rank-ribbon">IMDb #${movie.rank_no}</span>
            </div>
            <div class="details-copy">
                <div class="genre-pills">${genres}</div>
                <h1>${escapeHtml(movie.title)}</h1>
                <div class="details-meta">
                    <strong>★ ${Number(movie.imdb_rating).toFixed(1)}</strong>
                    <span>${escapeHtml(movie.release_year)}</span><span>${escapeHtml(runtime)}</span>
                    <span>${new Intl.NumberFormat('en', { notation: 'compact' }).format(movie.rating_count)} ratings</span>
                </div>
                <p class="details-description">${escapeHtml(movie.description)}</p>
                <div class="crew-grid">
                    <div><span>Director</span><strong>${escapeHtml(movie.director)}</strong></div>
                    <div><span>Cast</span><strong>${escapeHtml(movie.cast_text || 'Cast unavailable')}</strong></div>
                </div>
                <div class="details-actions">
                    <button id="detailFavourite" class="primary-button ${saved ? 'is-saved' : ''}">${saved ? '♥ Saved to My List' : '♡ Add to My List'}</button>
                    <a class="ghost-button" href="${imdbUrl}" target="_blank" rel="noopener noreferrer">Open IMDb ↗</a>
                </div>
            </div>
        </section>
        <section class="related-section">
            <div class="section-heading"><div><span class="section-kicker">Keep exploring</span><h2>More like this</h2></div></div>
           <div class="related-grid">${
    Array.isArray(related) && related.length
        ? related.map(relatedCard).join('')
        : '<p>No related movies found.</p>'
}</div>
        </section>`;

    document.querySelector('#detailFavourite').addEventListener('click', () => toggleFavourite(Number(movie.id)));
}
loadMovie();
