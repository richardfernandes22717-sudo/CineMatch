<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

require_login();

$user = current_user();

if ($user === null) {
    redirect('/movieverse/auth/login.php');
}

$flash = get_flash();

$totalMovies = 0;
$topMovies = [];
$recentMovies = [];

try {
    /*
    |--------------------------------------------------------------------------
    | Total movies
    |--------------------------------------------------------------------------
    */

    $countStatement = db()->query(
        'SELECT COUNT(*) FROM movies'
    );

    $totalMovies = (int) $countStatement->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Top-rated movies
    |--------------------------------------------------------------------------
    */

    $topStatement = db()->query(
        'SELECT
            id,
            title,
            poster_url,
            release_year,
            imdb_rating,
            genres
         FROM movies
         WHERE imdb_rating IS NOT NULL
         ORDER BY imdb_rating DESC, id ASC
         LIMIT 6'
    );

    $topMovies = $topStatement->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | Recently added movies
    |--------------------------------------------------------------------------
    */

    $recentStatement = db()->query(
        'SELECT
            id,
            title,
            poster_url,
            release_year,
            imdb_rating,
            genres
         FROM movies
         ORDER BY id DESC
         LIMIT 6'
    );

    $recentMovies = $recentStatement->fetchAll();
} catch (PDOException $error) {
    $topMovies = [];
    $recentMovies = [];
}

function first_genre(?string $genres): string
{
    if (!$genres) {
        return 'Movie';
    }

    $parts = array_map('trim', explode(',', $genres));

    return $parts[0] ?: 'Movie';
}

function poster_url(?string $poster): string
{
    if (!$poster) {
        return '/movieverse/assets/img/fallback-poster.svg';
    }

    return $poster;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Dashboard | CineMatch</title>

    <style>
        :root {
            --background: #07070b;
            --surface: #111118;
            --surface-light: #191923;
            --border: rgba(255, 255, 255, 0.1);
            --text: #f7f7fb;
            --muted: #a4a4b3;
            --primary: #ff4d6d;
            --secondary: #8b5cf6;
            --success: #3ddc97;
            --danger: #ff6b6b;
            --shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at top left,
                    rgba(139, 92, 246, 0.15),
                    transparent 30%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(255, 77, 109, 0.12),
                    transparent 32%
                ),
                var(--background);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            display: block;
            max-width: 100%;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            min-height: 76px;
            padding: 0 6%;
            border-bottom: 1px solid var(--border);
            background: rgba(7, 7, 11, 0.88);
            backdrop-filter: blur(20px);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .brand-logo {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--secondary)
                );
            box-shadow: 0 12px 28px rgba(255, 77, 109, 0.25);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link {
            padding: 10px 14px;
            border-radius: 11px;
            color: var(--muted);
            font-size: 0.92rem;
            font-weight: 700;
            transition:
                color 0.2s ease,
                background 0.2s ease;
        }

        .nav-link:hover {
            color: var(--text);
            background: rgba(255, 255, 255, 0.06);
        }

        .logout-link {
            color: #ffd3d8;
            background: rgba(255, 77, 109, 0.12);
        }

        .logout-link:hover {
            color: white;
            background: rgba(255, 77, 109, 0.22);
        }

        .main-container {
            width: min(1180px, calc(100% - 36px));
            margin: 0 auto;
            padding: 42px 0 70px;
        }

        .flash-message {
            margin-bottom: 24px;
            padding: 15px 18px;
            border: 1px solid rgba(61, 220, 151, 0.32);
            border-radius: 15px;
            color: #c6f7df;
            background: rgba(61, 220, 151, 0.1);
        }

        .hero {
            position: relative;
            overflow: hidden;
            padding: 44px;
            border: 1px solid var(--border);
            border-radius: 30px;
            background:
                linear-gradient(
                    135deg,
                    rgba(255, 77, 109, 0.14),
                    rgba(139, 92, 246, 0.12)
                ),
                var(--surface);
            box-shadow: var(--shadow);
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -90px;
            bottom: -100px;
            border-radius: 50%;
            background: rgba(255, 77, 109, 0.15);
            filter: blur(10px);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 720px;
        }

        .eyebrow {
            display: inline-flex;
            margin-bottom: 16px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: #ffb5c2;
            background: rgba(255, 255, 255, 0.05);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero h1 {
            margin-bottom: 14px;
            font-size: clamp(2.2rem, 5vw, 4.5rem);
            line-height: 1;
            letter-spacing: -0.06em;
        }

        .gradient-text {
            background:
                linear-gradient(
                    90deg,
                    #ff6b81,
                    #bd8cff,
                    #7dd3fc
                );
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero p {
            max-width: 650px;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.75;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 26px;
        }

        .primary-button,
        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 19px;
            border-radius: 13px;
            font-weight: 800;
        }

        .primary-button {
            color: white;
            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    #d93658
                );
            box-shadow: 0 14px 30px rgba(255, 77, 109, 0.23);
        }

        .secondary-button {
            border: 1px solid var(--border);
            color: #dedee7;
            background: rgba(255, 255, 255, 0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 24px;
        }

        .stat-card {
            padding: 22px;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: var(--surface);
        }

        .stat-card span {
            display: block;
            margin-bottom: 10px;
            color: var(--muted);
            font-size: 0.86rem;
            font-weight: 700;
        }

        .stat-card strong {
            font-size: 1.65rem;
            letter-spacing: -0.04em;
        }

        .section {
            margin-top: 45px;
        }

        .section-header {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 1.8rem;
            letter-spacing: -0.04em;
        }

        .section-header p {
            margin-top: 6px;
            color: var(--muted);
        }

        .view-all {
            color: #ff8299;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .movies-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 17px;
        }

        .movie-card {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--surface);
            transition:
                transform 0.22s ease,
                border-color 0.22s ease,
                box-shadow 0.22s ease;
        }

        .movie-card:hover {
            transform: translateY(-6px);
            border-color: rgba(255, 77, 109, 0.45);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
        }

        .poster-wrapper {
            position: relative;
            aspect-ratio: 2 / 3;
            overflow: hidden;
            background: var(--surface-light);
        }

        .poster-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .movie-card:hover .poster-wrapper img {
            transform: scale(1.04);
        }

        .rating-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 7px 9px;
            border-radius: 10px;
            color: #fff1b8;
            background: rgba(7, 7, 11, 0.82);
            backdrop-filter: blur(10px);
            font-size: 0.78rem;
            font-weight: 800;
        }

        .movie-info {
            padding: 14px;
        }

        .movie-info h3 {
            min-height: 42px;
            margin-bottom: 8px;
            overflow: hidden;
            font-size: 0.95rem;
            line-height: 1.35;
        }

        .movie-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            color: var(--muted);
            font-size: 0.78rem;
        }

        .empty-message {
            grid-column: 1 / -1;
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: 18px;
            color: var(--muted);
            text-align: center;
            background: var(--surface);
        }

        .profile-panel {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 20px;
            align-items: center;
            padding: 24px;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: var(--surface);
        }

        .avatar {
            display: grid;
            place-items: center;
            width: 72px;
            height: 72px;
            border-radius: 22px;
            color: white;
            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--secondary)
                );
            font-size: 1.8rem;
            font-weight: 900;
        }

        .profile-panel h3 {
            margin-bottom: 7px;
            font-size: 1.3rem;
        }

        .profile-panel p {
            color: var(--muted);
            line-height: 1.6;
        }

        @media (max-width: 1050px) {
            .movies-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .navbar {
                padding: 0 20px;
            }

            .nav-links .home-nav {
                display: none;
            }

            .main-container {
                width: min(100% - 24px, 1180px);
                padding-top: 24px;
            }

            .hero {
                padding: 30px 24px;
                border-radius: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .movies-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .section-header {
                align-items: start;
                flex-direction: column;
            }
        }

        @media (max-width: 430px) {
            .brand span:last-child {
                display: none;
            }

            .nav-link {
                padding: 9px 11px;
            }

            .hero h1 {
                font-size: 2.35rem;
            }

            .movies-grid {
                gap: 12px;
            }

            .movie-info {
                padding: 11px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <a class="brand" href="/movieverse/">
        <span class="brand-logo">▶</span>
        <span>CineMatch</span>
    </a>

    <div class="nav-links">
        <a class="nav-link home-nav" href="/movieverse/">
            Browse Movies
        </a>

        <a class="nav-link" href="/movieverse/user/dashboard.php">
            Dashboard
        </a>

        <a class="nav-link logout-link" href="/movieverse/auth/logout.php">
            Logout
        </a>
    </div>
</nav>

<main class="main-container">

    <?php if ($flash): ?>
        <div class="flash-message">
            <?= e((string) $flash['message']) ?>
        </div>
    <?php endif; ?>

    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Your Movie Dashboard</span>

            <h1>
                Welcome,
                <span class="gradient-text">
                    <?= e((string) $user['name']) ?>
                </span>
            </h1>

            <p>
                Explore highly rated movies, discover recent additions and
                continue using CineMatch to find films based on genres,
                ratings, decades and moods.
            </p>

            <div class="hero-actions">
                <a class="primary-button" href="/movieverse/">
                    Browse recommendations
                </a>

                <a class="secondary-button" href="#top-rated">
                    View top movies
                </a>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <span>Movies available</span>
            <strong><?= number_format($totalMovies) ?></strong>
        </article>

        <article class="stat-card">
            <span>Account role</span>
            <strong><?= e(ucfirst((string) $user['role'])) ?></strong>
        </article>

        <article class="stat-card">
            <span>Member since</span>
            <strong>
                <?= date('M Y', strtotime((string) $user['created_at'])) ?>
            </strong>
        </article>
    </section>

    <section class="section" id="top-rated">
        <div class="section-header">
            <div>
                <h2>Top-rated movies</h2>
                <p>Highly rated selections from the CineMatch database.</p>
            </div>

            <a class="view-all" href="/movieverse/">
                View all movies →
            </a>
        </div>

        <div class="movies-grid">
            <?php if (!empty($topMovies)): ?>
                <?php foreach ($topMovies as $movie): ?>
                    <a
                        class="movie-card"
                        href="/movieverse/movie.php?id=<?= (int) $movie['id'] ?>"
                    >
                        <div class="poster-wrapper">
                            <img
                                src="<?= e(poster_url($movie['poster_url'] ?? null)) ?>"
                                alt="<?= e((string) $movie['title']) ?>"
                                loading="lazy"
                                onerror="
                                    this.onerror = null;
                                    this.src = '/movieverse/assets/img/fallback-poster.svg';
                                "
                            >

                            <span class="rating-badge">
                                ★ <?= e((string) ($movie['imdb_rating'] ?? 'N/A')) ?>
                            </span>
                        </div>

                        <div class="movie-info">
                            <h3><?= e((string) $movie['title']) ?></h3>

                            <div class="movie-meta">
                                <span>
                                    <?= e((string) ($movie['release_year'] ?? 'N/A')) ?>
                                </span>

                                <span>
                                    <?= e(first_genre($movie['genre'] ?? null)) ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">
                    No movie records were found.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <div>
                <h2>Recently added</h2>
                <p>The latest records available in the movie database.</p>
            </div>
        </div>

        <div class="movies-grid">
            <?php if (!empty($recentMovies)): ?>
                <?php foreach ($recentMovies as $movie): ?>
                    <a
                        class="movie-card"
                        href="/movieverse/movie.php?id=<?= (int) $movie['id'] ?>"
                    >
                        <div class="poster-wrapper">
                            <img
                                src="<?= e(poster_url($movie['poster_url'] ?? null)) ?>"
                                alt="<?= e((string) $movie['title']) ?>"
                                loading="lazy"
                                onerror="
                                    this.onerror = null;
                                    this.src = '/movieverse/assets/img/fallback-poster.svg';
                                "
                            >

                            <span class="rating-badge">
                                ★ <?= e((string) ($movie['imdb_rating'] ?? 'N/A')) ?>
                            </span>
                        </div>

                        <div class="movie-info">
                            <h3><?= e((string) $movie['title']) ?></h3>

                            <div class="movie-meta">
                                <span>
                                    <?= e((string) ($movie['release_year'] ?? 'N/A')) ?>
                                </span>

                                <span>
                                    <?= e(first_genre($movie['genre']?? null)) ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">
                    No recent movies were found.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <div>
                <h2>Your account</h2>
                <p>Your currently registered CineMatch account information.</p>
            </div>
        </div>

        <div class="profile-panel">
            <div class="avatar">
                <?= e(strtoupper(substr((string) $user['name'], 0, 1))) ?>
            </div>

            <div>
                <h3><?= e((string) $user['name']) ?></h3>

                <p>
                    <?= e((string) $user['email']) ?><br>
                    Account status: <?= e(ucfirst((string) $user['status'])) ?>
                </p>
            </div>
        </div>
    </section>

</main>

</body>
</html>