<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#09090d">
    <title>Movie details — CineMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="details-body">
<div class="ambient ambient-one"></div>
<header class="site-header">
    <a class="brand" href="index.php"><span class="brand-mark">C</span><span>CineMatch</span></a>
    <a class="back-link" href="index.php">← Back to discover</a>
</header>
<main id="detailsRoot" class="details-root shell">
    <div class="details-loading"><div class="loader"></div><p>Loading movie...</p></div>
</main>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="assets/js/details.js" defer></script>
</body>
</html>
