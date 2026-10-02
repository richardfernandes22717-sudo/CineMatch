
<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($id === false || $id === null || $id < 1) {
        json_response([
            'success' => false,
            'message' => 'Invalid movie ID.',
        ], 422);
    }

    // Fetch the movie before updating its view count.
    $stmt = $pdo->prepare(
        'SELECT id, imdb_id, rank_no, title, release_year, genre,
                director, cast_text, description, poster_url,
                imdb_rating, rating_count, runtime, views, created_at
         FROM movies
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $movie = $stmt->fetch();

    if (!$movie) {
        json_response([
            'success' => false,
            'message' => 'Movie not found.',
        ], 404);
    }

    // Increment views only for an existing movie.
    $updateStmt = $pdo->prepare(
        'UPDATE movies SET views = views + 1 WHERE id = ?'
    );
    $updateStmt->execute([$id]);

    $movie['views'] = (int) $movie['views'] + 1;

    // Find related movies using the primary genre.
    $primaryGenre = trim(explode(',', (string) $movie['genre'])[0]);
    $related = [];

    if ($primaryGenre !== '') {
        $relatedStmt = $pdo->prepare(
            'SELECT id, title, release_year, genre, poster_url,
                    imdb_rating, director
             FROM movies
             WHERE id <> :id
               AND genre LIKE :genre
             ORDER BY imdb_rating DESC, rating_count DESC
             LIMIT 8'
        );

        $relatedStmt->execute([
            ':id' => $id,
            ':genre' => '%' . $primaryGenre . '%',
        ]);

        $related = $relatedStmt->fetchAll();
    }

    json_response([
        'success' => true,
        'movie' => $movie,
        'related' => $related,
    ]);
} catch (Throwable $error) {
    api_error($error);
}
