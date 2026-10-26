<?php
/** @var array|null $job */
/** @var list<array<string, mixed>> $openJobs */
/** @var array $outputs */
/** @var bool $freeMode */
$latest = $outputs ? end($outputs) : null;
?>
<section class="studio wrap">
  <h1 class="display">Studio</h1>
  <?php if (!empty($error)): ?><p class="alert"><?= e((string) $error) ?></p><?php endif; ?>
  <?php if (!$job): ?>
    <p>No active job yet. <?= $freeMode ? 'Start a free test job.' : 'Unlock the studio for $4.99.' ?></p>
    <form method="post" action="/checkout" class="qty-buy">
      <?= \App\Security\Csrf::field() ?>
      <label>Pages
        <input type="number" name="quantity" min="1" max="12" value="1">
      </label>
      <button class="btn" type="submit"><?= $freeMode ? 'Start free jobs' : 'Buy pages — $4.99 each' ?></button>
    </form>
  <?php else: ?>
    <?php if (count($openJobs) > 1): ?>
      <nav class="job-switch">
        <?php foreach ($openJobs as $index => $open): ?>
          <a href="/studio?job=<?= e($open['id']) ?>"<?= $open['id'] === $job['id'] ? ' aria-current="page"' : '' ?>>Page <?= count($openJobs) - $index ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
    <p class="kicker"><?= (int) $job['max_attempts'] - (int) $job['attempts_used'] ?> / <?= (int) $job['max_attempts'] ?> attempts left</p>
    <form method="post" action="/studio/process" enctype="multipart/form-data" class="studio-grid">
      <?= \App\Security\Csrf::field() ?>
      <input type="hidden" name="job_id" value="<?= e($job['id']) ?>">
      <div>
        <p class="hint">Zip tone, greyscale, or meticulous detail in the page can reduce the quality of the flats. Clean black line art works best.</p>
        <label class="drop">
          Line art
          <input id="lineart" type="file" name="file" accept="image/png,image/jpeg,image/webp,image/tiff">
        </label>
        <img id="preview" alt="" hidden>
        <?php if (!empty($job['input_path'])): ?>
          <img src="/uploads/<?= e($job['input_path']) ?>" alt="Current upload">
        <?php endif; ?>
        <label>Style notes
          <textarea name="style_notes" rows="3" maxlength="500"><?= e($job['style_notes'] ?? '') ?></textarea>
        </label>
        <label>Region hint <input type="number" name="region_hint" min="2" max="64" value="<?= e((string) ($job['region_hint'] ?? '')) ?>"></label>
        <label>Seed <input type="number" name="seed" min="0" value="<?= e((string) ($job['seed'] ?? '')) ?>"></label>
        <button class="btn" type="submit">Generate flats</button>
      </div>
      <div class="result">
        <h2 class="display">Flats</h2>
        <p>Color shapes only — no black inks — so the magic wand selects a region cleanly. Stack the line-art PNG above this in Photoshop or Clip Studio.</p>
        <?php if ($latest): ?>
          <img src="/uploads/<?= e($latest) ?>" alt="Generated flats without line art">
          <a class="btn" href="/uploads/<?= e($latest) ?>" download>Download flats</a>
          <?php if (!empty($job['line_path'])): ?>
            <a class="btn" href="/uploads/<?= e($job['line_path']) ?>" download>Download line art</a>
          <?php endif; ?>
        <?php else: ?>
          <p>Your flats preview will show up here.</p>
        <?php endif; ?>
      </div>
    </form>
    <form method="post" action="/checkout" class="qty-buy">
      <?= \App\Security\Csrf::field() ?>
      <label>Buy more pages
        <input type="number" name="quantity" min="1" max="12" value="1">
      </label>
      <button class="btn secondary" type="submit"><?= $freeMode ? 'Add free pages' : 'Add pages — $4.99 each' ?></button>
    </form>
  <?php endif; ?>
</section>
<script src="/assets/js/studio.js"></script>
