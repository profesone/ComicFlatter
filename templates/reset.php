<section class="auth">
  <h1 class="display">New password</h1>
  <?php if (!empty($error)): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/reset" class="stack">
    <?= \App\Security\Csrf::field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <input type="hidden" name="email" value="<?= e($email) ?>">
    <label>New password <input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
    <button class="btn" type="submit">Save password</button>
  </form>
</section>
