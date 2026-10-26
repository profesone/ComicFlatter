<?php
/** @var string $title */
/** @var string $path */
/** @var array|null $user */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'ComicFlats') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,520;9..144,640&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="site-header">
    <div class="wrap header-row">
      <a class="brand" href="/">Comic-Subs</a>
      <nav>
        <a href="/flatter">Flatter</a>
        <a href="/balloons">Balloons</a>
        <a href="/jobs">Jobs</a>
        <?php if ($user): ?>
          <a href="/account">Profile</a>
          <a class="nav-cta" href="/studio">Studio</a>
          <form method="post" action="/logout" class="inline">
            <?= \App\Security\Csrf::field() ?>
            <button type="submit" class="linkish">Log out</button>
          </form>
        <?php else: ?>
          <a href="/login">Log in</a>
          <a class="nav-cta" href="/register">Create account</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>
  <main>
    <?php require $path; ?>
  </main>
  <footer class="site-footer">
    <div class="wrap footer-row">
      <div>
        <p class="brand">Comic-Subs</p>
        <p>A studio suite for comic production: flats, balloon placement, and a creator job board.</p>
        <p>© <?= date('Y') ?> 500 Lines Media LLC</p>
      </div>
      <div class="footer-links">
        <a href="/privacy">Privacy</a>
        <a href="/terms">Terms</a>
        <a href="/conduct">Code of conduct</a>
        <p>Payments by Stripe</p>
      </div>
    </div>
  </footer>
</body>
</html>
