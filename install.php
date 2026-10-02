<?php
declare(strict_types=1);
const DB_HOST = '127.0.0.1';
const DB_PORT = '3308';
const DB_NAME = 'movieverse';
const DB_USER = 'root';
const DB_PASS = '';
$posterSource = 'https://gist.github.com/pjbelo/c5e28dbb7a8fea6713a2fd74f1cd80ae/raw/a9455e912cb2b8be27c326002ba7b93bfb77afab/top250movies.json';
$genreSource = 'https://gist.github.com/nsatyaprakash22/e447495dcf6b61861e983476250ff0cd/raw/3c9a923f765742ef516a08213e05b1fa50ea714b/IMDB_TOP250.csv';
$descriptionSource = 'https://raw.githubusercontent.com/suyashi29/python-su/master/data/IMDB-Movie-Data.csv';
function fetch_url(string $url): string
{
    $curlError = '';
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_TIMEOUT => 35,
            CURLOPT_USERAGENT => 'CineMatch-XAMPP-Installer/1.0',
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        if (is_string($body) && $body !== '' && $status >= 200 && $status < 400) {
            return $body;
        }
    }
    $context = stream_context_create([
        'http' => [
            'timeout' => 35,
            'follow_location' => 1,
            'header' => "User-Agent: CineMatch-XAMPP-Installer/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if (is_string($body) && $body !== '') {
        return $body;
    }

    $detail = $curlError !== '' ? " cURL said: {$curlError}" : '';
    throw new RuntimeException('Could not download the movie seed. Check your internet connection and enable PHP cURL in XAMPP.' . $detail);
}
function normalize_text(string $value): string
{
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = $converted !== false ? $converted : $value;
    $value = strtolower($value);
    return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
}
function parse_genre_csv(string $csv): array
{
    $byTitle = [];
    $byDirector = [];
    $lines = preg_split('/\R/u', trim($csv)) ?: [];

    foreach ($lines as $lineNumber => $line) {
        if ($lineNumber === 0 || trim($line) === '') {
            continue;
        }

        $parts = str_getcsv($line, ',', '"', '');
        if (count($parts) < 9 || !ctype_digit(trim((string) $parts[0]))) {
            continue;
        }

        $yearIndex = null;
        for ($i = 2, $count = count($parts); $i < min($count, 8); $i++) {
            $candidate = trim((string) $parts[$i]);
            if (preg_match('/^(18|19|20)\d{2}$/', $candidate)) {
                $yearIndex = $i;
                break;
            }
        }
        if ($yearIndex === null || count($parts) < $yearIndex + 7) {
            continue;
        }
        $title = trim(implode(',', array_slice($parts, 1, $yearIndex - 1)));
        $year = (int) trim((string) $parts[$yearIndex]);
        $genre0 = trim((string) ($parts[$yearIndex + 1] ?? ''));
        $director = trim((string) ($parts[$yearIndex + 2] ?? ''));
        $rating = (float) trim((string) ($parts[$yearIndex + 3] ?? '0'));
        $actors = array_values(array_filter(array_map('trim', array_slice($parts, $yearIndex + 4, 3))));
        $extraGenres = array_values(array_filter(array_map('trim', array_slice($parts, $yearIndex + 7, 2))));
        $genres = array_values(array_unique(array_filter(array_merge([$genre0], $extraGenres))));
        $record = [
            'rank' => (int) $parts[0],
            'title' => $title,
            'year' => $year,
            'genre' => implode(', ', $genres) ?: 'Drama',
            'director' => $director ?: 'Unknown',
            'rating' => $rating,
            'cast' => implode(', ', $actors),
        ];
        $byTitle[normalize_text($title) . '|' . $year] = $record;
        if ($director !== '') {
            $byDirector[normalize_text($director) . '|' . $year] = $record;
        }
    }
    return [$byTitle, $byDirector];
}
function parse_description_csv(string $csv): array
{
    $records = [];
    $handle = fopen('php://temp', 'r+');
    if ($handle === false) {
        return $records;
    }
    fwrite($handle, $csv);
    rewind($handle);
    $headers = fgetcsv($handle, null, ',', '"', '');
    if (!is_array($headers)) {
        fclose($handle);
        return $records;
    }
    $headers = array_map(static fn ($header) => preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $header)), $headers);
    $map = array_flip($headers);
    while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
        $title = trim((string) ($row[$map['Title'] ?? -1] ?? ''));
        $year = (int) ($row[$map['Year'] ?? -1] ?? 0);
        if ($title === '' || $year === 0) {
            continue;
        }

        $records[normalize_text($title) . '|' . $year] = [
            'description' => trim((string) ($row[$map['Description'] ?? -1] ?? '')),
            'genre' => trim((string) ($row[$map['Genre'] ?? -1] ?? '')),
            'director' => trim((string) ($row[$map['Director'] ?? -1] ?? '')),
            'cast' => trim((string) ($row[$map['Actors'] ?? -1] ?? '')),
            'runtime' => (int) ($row[$map['Runtime (Minutes)'] ?? -1] ?? 0),
        ];
    }

    fclose($handle);
    return $records;
}

function split_crew(string $crew): array
{
    if (preg_match('/^(.*?)\s*\(dir\.\),\s*(.*)$/u', $crew, $match)) {
        return [trim($match[1]), trim($match[2])];
    }

    $parts = array_map('trim', explode(',', $crew));
    $director = array_shift($parts) ?: 'Unknown';
    return [$director, implode(', ', $parts)];
}

$status = null;
$error = null;
$inserted = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $pdo = new PDO($serverDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $schema = file_get_contents(__DIR__ . '/database.sql');
        if (!is_string($schema)) {
            throw new RuntimeException('database.sql is missing.');
        }
        foreach (preg_split('/;\s*(?:\R|$)/', trim($schema)) ?: [] as $statementSql) {
            if (trim($statementSql) !== '') {
                $pdo->exec($statementSql);
            }
        }
        $pdo->exec('USE `' . DB_NAME . '`');

        /*
         * A previous CineMatch project may already have a `movies` table with
         * a different structure. CREATE TABLE IF NOT EXISTS cannot update that
         * old table, so preserve it as a timestamped backup and create the
         * schema required by this application.
         */
        $requiredColumns = [
            'id', 'imdb_id', 'rank_no', 'title', 'release_year', 'genre',
            'director', 'cast_text', 'description', 'poster_url',
            'imdb_rating', 'rating_count', 'runtime', 'views', 'created_at',
        ];

        $tableExistsStatement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = :database_name AND table_name = :table_name'
        );
        $tableExistsStatement->execute([
            ':database_name' => DB_NAME,
            ':table_name' => 'movies',
        ]);

        if ((int) $tableExistsStatement->fetchColumn() === 1) {
            $columnStatement = $pdo->query('SHOW COLUMNS FROM `movies`');
            $existingColumns = array_column($columnStatement->fetchAll(), 'Field');
            $missingColumns = array_diff($requiredColumns, $existingColumns);

            if ($missingColumns !== []) {
                $backupTable = 'movies_backup_' . date('Ymd_His');
                $suffix = 1;

                while (true) {
                    $tableExistsStatement->execute([
                        ':database_name' => DB_NAME,
                        ':table_name' => $backupTable,
                    ]);
                    if ((int) $tableExistsStatement->fetchColumn() === 0) {
                        break;
                    }
                    $backupTable = 'movies_backup_' . date('Ymd_His') . '_' . $suffix++;
                }

                $pdo->exec(sprintf(
                    'RENAME TABLE `movies` TO `%s`',
                    str_replace('`', '``', $backupTable)
                ));

                foreach (preg_split('/;\s*(?:\R|$)/', trim($schema)) ?: [] as $statementSql) {
                    if (trim($statementSql) !== '') {
                        $pdo->exec($statementSql);
                    }
                }
                $pdo->exec('USE `' . DB_NAME . '`');
            }
        }

        $posterJson = fetch_url($posterSource);
        $posterData = json_decode($posterJson, true, 512, JSON_THROW_ON_ERROR);
        $movies = $posterData['top250movies'] ?? [];
        if (!is_array($movies) || count($movies) < 200) {
            throw new RuntimeException('The downloaded poster dataset is incomplete.');
        }

        $genreCsv = fetch_url($genreSource);
        [$genreByTitle, $genreByDirector] = parse_genre_csv($genreCsv);

        $descriptionByTitle = [];
        try {
            $descriptionByTitle = parse_description_csv(fetch_url($descriptionSource));
        } catch (Throwable) {
            // Optional enrichment source. Installation can continue without it.
        }

        $sql = 'INSERT INTO movies
            (imdb_id, rank_no, title, release_year, genre, director, cast_text, description, poster_url, imdb_rating, rating_count, runtime)
            VALUES
            (:imdb_id, :rank_no, :title, :release_year, :genre, :director, :cast_text, :description, :poster_url, :imdb_rating, :rating_count, :runtime)
            ON DUPLICATE KEY UPDATE
                rank_no = VALUES(rank_no),
                title = VALUES(title),
                release_year = VALUES(release_year),
                genre = VALUES(genre),
                director = VALUES(director),
                cast_text = VALUES(cast_text),
                description = VALUES(description),
                poster_url = VALUES(poster_url),
                imdb_rating = VALUES(imdb_rating),
                rating_count = VALUES(rating_count),
                runtime = VALUES(runtime)';
        $statement = $pdo->prepare($sql);

        $pdo->beginTransaction();
        foreach (array_slice($movies, 0, 250) as $movie) {
            $title = trim((string) ($movie['title'] ?? ''));
            $year = (int) ($movie['year'] ?? 0);
            if ($title === '' || $year === 0) {
                continue;
            }

            [$posterDirector, $posterCast] = split_crew((string) ($movie['crew'] ?? ''));
            $titleKey = normalize_text($title) . '|' . $year;
            $directorKey = normalize_text($posterDirector) . '|' . $year;
            $genreInfo = $genreByTitle[$titleKey] ?? $genreByDirector[$directorKey] ?? null;
            $descriptionInfo = $descriptionByTitle[$titleKey] ?? null;

            $genre = trim((string) ($genreInfo['genre'] ?? $descriptionInfo['genre'] ?? 'Drama')) ?: 'Drama';
            $director = trim((string) ($genreInfo['director'] ?? $descriptionInfo['director'] ?? $posterDirector)) ?: 'Unknown';
            $cast = trim((string) ($genreInfo['cast'] ?? $descriptionInfo['cast'] ?? $posterCast));
            $runtime = (int) ($descriptionInfo['runtime'] ?? 0);
            $description = trim((string) ($descriptionInfo['description'] ?? ''));

            if ($description === '') {
                $lead = $cast !== '' ? ' featuring ' . $cast : '';
                $description = sprintf(
                    '%s is a highly rated %s film directed by %s%s. It is included among cinema’s most acclaimed and audience-loved movies.',
                    $title,
                    strtolower($genre),
                    $director,
                    $lead
                );
            }

            $numericImdbId = (string) ($movie['id'] ?? '0');
            $imdbId = 'tt' . str_pad($numericImdbId, 7, '0', STR_PAD_LEFT);
            $posterUrl = str_replace('http://', 'https://', trim((string) ($movie['image'] ?? '')));
            $posterUrl = preg_replace('/\._V1_.*\.jpg$/', '._V1_FMjpg_UX600_.jpg', $posterUrl) ?: $posterUrl;

            $statement->execute([
                ':imdb_id' => $imdbId,
                ':rank_no' => (int) ($movie['rank'] ?? 0),
                ':title' => $title,
                ':release_year' => $year,
                ':genre' => $genre,
                ':director' => $director,
                ':cast_text' => $cast,
                ':description' => $description,
                ':poster_url' => $posterUrl,
                ':imdb_rating' => (float) ($movie['imDbRating'] ?? $genreInfo['rating'] ?? 0),
                ':rating_count' => (int) ($movie['imDbRatingCount'] ?? 0),
                ':runtime' => $runtime > 0 ? $runtime : null,
            ]);
            $inserted++;
        }
        $pdo->commit();

        $count = (int) $pdo->query('SELECT COUNT(*) FROM movies')->fetchColumn();
        $status = "Installation complete. {$count} movies are ready.";
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install CineMatch</title>
    <style>
        :root { color-scheme: dark; --bg:#09090d; --card:#15151d; --text:#f7f7fb; --muted:#a5a6b3; --accent:#ff4d6d; --accent2:#7c5cff; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; font-family:Inter,system-ui,sans-serif; background:radial-gradient(circle at top left,#251a38 0,transparent 38%),radial-gradient(circle at bottom right,#32131b 0,transparent 34%),var(--bg); color:var(--text); }
        .card { width:min(720px,100%); padding:34px; border:1px solid rgba(255,255,255,.1); border-radius:28px; background:rgba(21,21,29,.92); box-shadow:0 30px 90px rgba(0,0,0,.45); }
        .eyebrow { color:#ff9cae; font-weight:800; letter-spacing:.16em; text-transform:uppercase; font-size:.75rem; }
        h1 { margin:.55rem 0 .5rem; font-size:clamp(2rem,6vw,3.7rem); letter-spacing:-.055em; }
        p { color:var(--muted); line-height:1.65; }
        .steps { display:grid; gap:10px; margin:24px 0; }
        .step { display:flex; gap:12px; padding:14px 16px; background:#0f0f15; border-radius:16px; color:#d8d8e2; }
        .step b { color:white; }
        button,.open { display:inline-flex; align-items:center; justify-content:center; min-height:50px; padding:0 22px; border:0; border-radius:14px; color:white; text-decoration:none; font-weight:800; cursor:pointer; background:linear-gradient(135deg,var(--accent),var(--accent2)); }
        .notice { margin:18px 0; padding:14px 16px; border-radius:14px; }
        .success { background:rgba(45,211,111,.12); border:1px solid rgba(45,211,111,.35); color:#9df2bd; }
        .error { background:rgba(255,77,109,.12); border:1px solid rgba(255,77,109,.4); color:#ffb1bf; }
        code { background:#09090d; padding:.18rem .4rem; border-radius:6px; }
        form { margin-top:22px; }
    </style>
</head>
<body>
<main class="card">
    <div class="eyebrow">One-click XAMPP setup</div>
    <h1>Install CineMatch</h1>
    <p>This sets up the CineMatch Movie Recommendation System, creates the <code>movieverse</code> MySQL database, and imports 250 movies. Keep MySQL running and use the default XAMPP root account.</p>

    <div class="steps">
        <div class="step"><b>1.</b><span>Start Apache and MySQL in XAMPP.</span></div>
        <div class="step"><b>2.</b><span>Keep internet enabled during this one-time import.</span></div>
        <div class="step"><b>3.</b><span>Press Install, then open the app.</span></div>
    </div>

    <?php if ($status !== null): ?>
        <div class="notice success"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></div>
        <a class="open" href="index.php">Open CineMatch</a>
    <?php else: ?>
        <?php if ($error !== null): ?>
            <div class="notice error"><strong>Installation failed:</strong><br><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post">
            <button type="submit">Install database and 250 movies</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
