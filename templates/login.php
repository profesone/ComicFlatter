<section class="auth">
  <h1 class="display">Log in</h1>
  <?php if (!empty($error)): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/login" class="stack">
    <?= \App\Security\Csrf::field() ?>
    <label>Email <input type="email" name="email" required autocomplete="email"></label>
    <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p><a href="/forgot">Forgot password?</a> · <a href="/register">Create an account</a></p>
</section>
