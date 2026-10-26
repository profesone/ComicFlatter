<?php /** @var bool $freeMode */ ?>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <p class="kicker">Comic-Subs</p>
      <p class="display hero-brand">Comic Flatter</p>
      <h1>Upload B&amp;W comic line art and get professional flats.</h1>
      <p class="lede">Clean solid colors for Photoshop and Clip Studio. Crisp regions. Ready to paint.</p>
      <p class="hint">Zip tone, greyscale, or meticulous detail in the upload can reduce the quality of the flats.</p>
      <div class="cta-row">
        <?php if ($user): ?>
          <form method="post" action="/checkout" class="qty-buy">
            <?= \App\Security\Csrf::field() ?>
            <label>Pages
              <input type="number" name="quantity" min="1" max="12" value="1">
            </label>
            <button class="btn" type="submit"><?= $freeMode ? 'Open studio — free' : 'Buy flats — $4.99 each' ?></button>
          </form>
        <?php else: ?>
          <a class="btn" href="/register"><?= $freeMode ? 'Create account — free' : 'Create account — $4.99' ?></a>
        <?php endif; ?>
        <a class="text-link" href="#gallery">See before / after</a>
      </div>
    </div>
    <div class="demo">
      <span class="badge">Demo</span>
      <div class="demo-grid">
        <img src="/assets/demos/lineart-hero.svg" alt="Sample comic line art">
        <img src="/assets/demos/flats-hero.svg" alt="Sample comic flats">
      </div>
    </div>
  </div>
</section>
<section id="gallery" class="section halftone">
  <div class="wrap">
    <h2 class="display">Before / after</h2>
    <p>Line art in. Distinct flat regions out — skin, cloth, props, and backgrounds separated for easy selection.</p>
    <div class="pair">
      <figure>
        <figcaption>Before · line art</figcaption>
        <img src="/assets/demos/lineart-hero.svg" alt="Hero portrait line art">
      </figure>
      <figure>
        <figcaption class="cyan">After · flats</figcaption>
        <img src="/assets/demos/flats-hero.svg" alt="Hero portrait flats">
      </figure>
    </div>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <h2 class="display">Why Comic Flatter</h2>
    <div class="benefits">
      <article><h3 class="display">Pro flatting style</h3><p>Solid regions, clean edges, trapping under the lines — built for real colorist workflows.</p></article>
      <article><h3 class="display">Minutes, not hours</h3><p>Skip tedious bucket-fills. Generate a first pass fast, then refine in Photoshop or CSP.</p></article>
      <article><h3 class="display">4 attempts included</h3><p>Regenerate with new seeds or style notes until the flats feel right — all in one $4.99 payment.</p></article>
    </div>
  </div>
</section>
<section id="pricing" class="section pricing">
  <div class="wrap price-row">
    <div>
      <h2 class="display">Simple pricing</h2>
      <p>One-time payment. No subscription. Pay when you need flats.</p>
      <ul>
        <li>$4.99 per page, and you can buy several pages at once</li>
        <li>Up to 4 generation attempts</li>
        <li>Downloadable PNG ready for import</li>
        <li>Account required so your jobs stay yours</li>
      </ul>
    </div>
    <div class="price-card">
      <p class="kicker">Single use</p>
      <p class="display price">$4.99</p>
      <p>Unlock the studio, upload your page, download flats.</p>
      <?php if ($user): ?>
        <form method="post" action="/checkout" class="qty-buy">
          <?= \App\Security\Csrf::field() ?>
          <label>Pages
            <input type="number" name="quantity" min="1" max="12" value="1">
          </label>
          <button class="btn" type="submit"><?= $freeMode ? 'Open studio — free' : 'Start flatting — $4.99 each' ?></button>
        </form>
      <?php else: ?>
        <a class="btn" href="/register">Create account</a>
      <?php endif; ?>
    </div>
  </div>
</section>
