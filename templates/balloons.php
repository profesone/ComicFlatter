<?php if (!$user): ?>
<section class="balloon-band">
  <div class="wrap balloon-layout">
    <div>
      <p class="kicker">In progress</p>
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
      <h2 class="display">Planned pricing</h2>
      <p>Balloon Popper is not for sale yet. The tier matches the rest of Comic-Subs.</p>
      <ul>
        <li>$4.99 per page when checkout opens</li>
        <li>Balloon placement on your uploaded page</li>
        <li>No checkout on this page today</li>
      </ul>
    </div>
    <article class="price-card">
      <p class="kicker">In progress</p>
      <p class="display price">$4.99</p>
      <p>Per page. Purchases will open later.</p>
      <a class="btn secondary" href="/login">Log in</a>
    </article>
  </div>
</section>
<?php else: ?>
<section class="section">
  <div class="wrap letter-tool" data-csrf="<?= e($csrf) ?>">
    <header class="letter-head">
      <p class="kicker">Logged in</p>
      <h1 class="display">Comic Balloon Popper</h1>
      <p>Upload a page, paste one line of dialogue per row, then place each balloon.</p>
    </header>

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
