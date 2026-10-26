<section class="auth">
  <h1 class="display">Create account</h1>
  <?php if (!empty($error)): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/register" class="stack">
    <?= \App\Security\Csrf::field() ?>
    <label>Name <input type="text" name="name" autocomplete="name" maxlength="120"></label>
    <label>Email <input type="email" name="email" required autocomplete="email"></label>
    <label>Password <input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
    <button class="btn" type="submit">Create account</button>
  </form>
  <p>Already have an account? <a href="/login">Log in</a></p>
</section>
