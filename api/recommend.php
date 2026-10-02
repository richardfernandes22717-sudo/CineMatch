<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();
    $mood = trim((string) ($_GET['mood'] ?? 'top'));
    $genre = trim((string) ($_GET['genre'] ?? ''));
    $limit = max(1, min(20, (int) ($_GET['limit'] ?? 12)));

    $where = ['imdb_rating >= 7.5'];
    $params = [];

    $moodRules = [
        'mind-bending' => '(genre LIKE "%Sci-Fi%" OR genre LIKE "%Mystery%" OR genre LIKE "%Thriller%")',
        'feel-good' => '(genre LIKE "%Comedy%" OR genre LIKE "%Family%" OR genre LIKE "%Romance%" OR genre LIKE "%Animation%")',
        'intense' => '(genre LIKE "%Action%" OR genre LIKE "%Crime%" OR genre LIKE "%Thriller%" OR genre LIKE "%War%")',
        'emotional' => '(genre LIKE "%Drama%" OR genre LIKE "%Biography%" OR genre LIKE "%Romance%")',
        'classic' => 'release_year < 1990',
        'modern' => 'release_year >= 2010',
        'hidden' => '(rank_no > 100 AND imdb_rating >= 8.0)',
        'top' => 'rank_no <= 60',
    ];
    $where[] = $moodRules[$mood] ?? $moodRules['top'];

    if ($genre !== '') {
        $where[] = 'genre LIKE :genre';
        $params[':genre'] = '%' . $genre . '%';
    }

    $sql = 'SELECT id, imdb_id, rank_no, title, release_year, genre, director, cast_text,
                   description, poster_url, imdb_rating, rating_count, runtime, views
            FROM movies
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY RAND()
            LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    json_response([
        'success' => true,
        'mood' => $mood,
        'movies' => $stmt->fetchAll(),
    ]);
} catch (Throwable $error) {
    api_error($error);
}
