<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$navUser = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <meta name="theme-color" content="#09090d">
    <meta
        name="description"
        content="CineMatch is a movie recommendation system built using PHP, MySQL, HTML, CSS and JavaScript."
    >
    <title>CineMatch — Movie Recommendation System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="ambient ambient-one"></div>
<div class="ambient ambient-two"></div>
<header class="site-header">
    <a
        class="brand"
        href="/movieverse/"
        aria-label="CineMatch home"
    >
        <span class="brand-mark">C</span>
        <span>CineMatch</span>
    </a>
    <nav class="nav-tabs" aria-label="Main navigation">
        <button
            type="button"
            class="nav-tab is-active"
            data-view="discover"
        >
            Discover
        </button>
        <button
            type="button"
            class="nav-tab"
            data-view="all"
        >
            All Movies
        </button>
        <button
            type="button"
            class="nav-tab"
            data-view="favourites"
        >
            My List

            <span
                id="favouriteCount"
                class="count-pill"
            >
                0
            </span>
        </button>
    </nav>
    <div class="auth-navigation">
        <?php if ($navUser !== null): ?>
            <a
                href="/movieverse/user/dashboard.php"
                class="auth-nav-link user-link"
                title="Open dashboard"
            >
                <span class="user-avatar">
                    <?= e(
                        strtoupper(
                            substr((string) $navUser['name'], 0, 1)
                        )
                    ) ?>
                </span>

                <span class="auth-user-name">
                    <?= e((string) $navUser['name']) ?>
                </span>
            </a>
            <a
                href="/movieverse/auth/logout.php"
                class="auth-nav-link logout-link"
            >
                Logout
            </a>
        <?php else: ?>
            <a
                href="/movieverse/auth/login.php"
                class="auth-nav-link login-link"
            >
                Login
            </a>

            <a
                href="/movieverse/auth/register.php"
                class="auth-nav-link register-link"
            >
                Register
            </a>
        <?php endif; ?>
    </div>
</header>
<main>
    <!-- Hero section -->
    <section class="hero shell" id="hero">

        <div class="hero-copy">
            <div class="eyebrow">
                <span></span>
                Personal movie discovery
            </div>
            <h1>
                Stop scrolling.<br>
                <em>Start watching.</em>
            </h1>
            <p>
                Discover top-rated movies based on mood, genre and
                personalised recommendation categories.
            </p>
            <div class="hero-actions">
                <button
                    type="button"
                    class="primary-button"
                    id="surpriseButton"
                >
                    Surprise Me
                </button>
                <button
                    type="button"
                    class="ghost-button"
                    data-scroll="#recommendations"
                >
                    Explore Recommendations
                </button>
            </div>
            <div class="hero-stats">
                <div>
                    <strong>250</strong>
                    <span>curated movies</span>
                </div>
                <div>
                    <strong>7</strong>
                    <span>recommendation moods</span>
                </div>
                <div>
                    <strong>Free</strong>
                    <span>movie discovery</span>
                </div>
            </div>
        </div>
        <article
            class="featured-card"
            id="featuredCard"
            aria-live="polite"
        >
            <div class="featured-skeleton"></div>
        </article>
    </section>
    <!-- Mood recommendations -->
    <section
        class="shell mood-section"
        id="recommendations"
    >

        <div class="section-heading">

            <div>
                <span class="section-kicker">
                    Recommendations for your current mood
                </span>

                <h2>
                    What are you in the mood for?
                </h2>
            </div>

            <button
                type="button"
                class="text-button"
                id="refreshRecommendations"
            >
                Refresh Picks ↻
            </button>

        </div>

        <div class="mood-row" id="moodRow">

            <button
                type="button"
                class="mood-chip is-active"
                data-mood="top"
            >
                Top Picks
            </button>

            <button
                type="button"
                class="mood-chip"
                data-mood="mind-bending"
            >
                Mind-Bending
            </button>

            <button
                type="button"
                class="mood-chip"
                data-mood="feel-good"
            >
                Feel-Good
            </button>

            <button
                type="button"
                class="mood-chip"
                data-mood="intense"
            >
                Intense
            </button>

            <button
                type="button"
                class="mood-chip"
                data-mood="emotional"
            >
                Emotional
            </button>

            <button
                type="button"
                class="mood-chip"
                data-mood="classic"
            >
                Classics
            </button>

            <button
                type="button"
                class="mood-chip"
                data-mood="hidden"
            >
                Hidden Gems
            </button>

        </div>

        <div
            class="movie-row"
            id="recommendationGrid"
        ></div>

    </section>

    <!-- Complete movie catalogue -->
    <section
        class="shell catalogue-section"
        id="catalogue"
    >

        <div class="section-heading">

            <div>
                <span class="section-kicker">
                    Browse the collection
                </span>

                <h2 id="catalogueTitle">
                    Explore All Movies
                </h2>
            </div>

            <span
                class="results-count"
                id="resultsCount"
            >
                Loading movies...
            </span>

        </div>

        <div
            class="movie-grid"
            id="movieGrid"
        ></div>

        <div
            class="empty-state hidden"
            id="emptyState"
        >
            <div class="empty-icon">🎬</div>

            <h3>No movies found</h3>

            <p>
                No movies are currently available in this section.
            </p>

            <button
                type="button"
                class="ghost-button"
                id="clearFiltersButton"
            >
                Show All Movies
            </button>
        </div>

        <div class="load-more-wrap">

            <button
                type="button"
                class="ghost-button hidden"
                id="loadMoreButton"
            >
                Load More Movies
            </button>

        </div>

    </section>

</main>

<!--
These fields are hidden from the page.

They preserve compatibility with the existing app.js if it expects
the old filter elements to exist.
-->
<div hidden aria-hidden="true">

    <input
        id="searchInput"
        type="search"
        value=""
        tabindex="-1"
    >

    <select id="genreFilter" tabindex="-1">
        <option value="" selected>All genres</option>
    </select>

    <select id="decadeFilter" tabindex="-1">
        <option value="" selected>Any decade</option>
    </select>

    <select id="ratingFilter" tabindex="-1">
        <option value="0" selected>Any rating</option>
    </select>

    <select id="sortFilter" tabindex="-1">
        <option value="rank" selected>IMDb rank</option>
    </select>

</div>

<div
    class="toast"
    id="toast"
    role="status"
    aria-live="polite"
></div>

<script src="assets/js/app.js" defer></script>

</body>
</html>