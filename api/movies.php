<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();

    $search = trim((string) ($_GET['search'] ?? ''));
    $genre = trim((string) ($_GET['genre'] ?? ''));
    $decade = trim((string) ($_GET['decade'] ?? ''));
    $minRating = max(0.0, min(10.0, (float) ($_GET['min_rating'] ?? 0)));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = max(1, min(40, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $allowedSorts = [
        'rank' => 'rank_no IS NULL ASC, rank_no ASC',
        'rating' => 'imdb_rating DESC, rating_count DESC',
        'popular' => 'rating_count DESC',
        'newest' => 'release_year DESC, imdb_rating DESC',
        'oldest' => 'release_year ASC, imdb_rating DESC',
        'title' => 'title ASC',
    ];
    $sort = (string) ($_GET['sort'] ?? 'rank');
    $orderBy = $allowedSorts[$sort] ?? $allowedSorts['rank'];

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(title LIKE :search_title OR director LIKE :search_director OR cast_text LIKE :search_cast OR genre LIKE :search_genre)';
        $searchValue = '%' . $search . '%';
        $params[':search_title'] = $searchValue;
        $params[':search_director'] = $searchValue;
        $params[':search_cast'] = $searchValue;
        $params[':search_genre'] = $searchValue;
    }

    if ($genre !== '') {
        $where[] = 'genre LIKE :genre';
        $params[':genre'] = '%' . $genre . '%';
    }

 if (preg_match('/^\d{4}$/', $decade)) {
        $start = (int) $decade;
        $where[] = 'release_year BETWEEN :start_year AND :end_year';
        $params[':start_year'] = $start;
        $params[':end_year'] = $start + 9;
    }

    if ($minRating > 0) {
        $where[] = 'imdb_rating >= :min_rating';
        $params[':min_rating'] = $minRating;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM movies {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = "SELECT id, imdb_id, rank_no, title, release_year, genre, director, cast_text,
                   description, poster_url, imdb_rating, rating_count, runtime, views
            FROM movies
            {$whereSql}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    json_response([
        'success' => true,
        'movies' => $stmt->fetchAll(),
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int) ceil($total / $limit),
            'has_more' => $offset + $limit < $total,
        ],
    ]);
} catch (Throwable $error) {
    api_error($error);
}
