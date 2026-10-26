<?php if (!$user): ?>
<section class="balloon-band">
  <div class="wrap balloon-layout">
    <div>
      <p class="kicker">Open</p>
      <h1 class="display">Comic Balloon Popper</h1>
      <p>Drop word balloons onto a finished page so lettering sits in the quiet space and the figures stay readable.</p>
      <a class="btn" href="/login">Log in to letter a page</a>
    </div>
    <div class="page-sheet" aria-hidden="true">
      <div class="panel"></div>
      <div class="panel"></div>
      <div class="balloon balloon-a">Wait—</div>
      <div class="balloon balloon-b">On it.</div>
    </div>
  </div>
</section>
<section class="section balloon-pricing">
  <div class="wrap price-row">
    <div>
      <h2 class="display">Pricing</h2>
      <p>One purchase covers one page. Unused pages stay on the account until you draw them.</p>
      <ul>
        <li>$4.99 per page</li>
        <li>Upload the page and map each line</li>
        <li>Download a transparent balloon overlay</li>
      </ul>
    </div>
    <article class="price-card">
      <p class="kicker">Per page</p>
      <p class="display price">$4.99</p>
      <p>Log in to buy a page and letter it.</p>
      <a class="btn" href="/login">Log in</a>
    </article>
  </div>
</section>
<?php else: ?>
<section class="section">
  <div class="wrap letter-tool" data-csrf="<?= e($csrf) ?>">
    <header class="letter-head">
      <p class="kicker">Logged in</p>
      <h1 class="display">Comic Balloon Popper</h1>
      <p>Upload a page, paste one line of dialogue per row, then place each balloon. You have <?= (int) $credits ?> page<?= (int) $credits === 1 ? '' : 's' ?> ready.</p>
    </header>
    <form method="post" action="/checkout" class="qty-buy">
      <?= \App\Security\Csrf::field() ?>
      <input type="hidden" name="product" value="balloon">
      <label>Pages
        <input type="number" name="quantity" min="1" max="12" value="1">
      </label>
      <button class="btn secondary" type="submit"><?= $freeMode ? 'Add free pages' : 'Buy pages — $4.99 each' ?></button>
    </form>

    <form id="page-form" class="card">
      <label>Page (JPG or PNG)
        <input type="file" name="page" accept="image/jpeg,image/png" required>
      </label>
      <button class="btn" type="submit">Upload page</button>
      <p id="upload-status" class="hint"></p>
    </form>

    <div id="mapper" hidden>
      <label>Panels
        <input id="panel-count" type="number" min="1" max="9" value="4">
      </label>
      <label>Script
        <textarea id="script" rows="6" placeholder="One line of dialogue per row"></textarea>
      </label>
      <button class="btn secondary" id="build-lines" type="button">Map lines</button>
      <div id="lines"></div>
      <button class="btn" id="generate" type="button">Draw balloons</button>
      <p id="gen-status" class="hint"></p>
      <div class="preview-frame" id="preview" hidden>
        <img id="page-img" alt="Uploaded comic page">
        <img id="overlay-img" alt="Balloon overlay">
      </div>
    </div>
  </div>
</section>
<script src="/assets/js/balloons.js"></script>
<?php endif; ?>
