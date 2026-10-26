<section class="auth">
  <h1 class="display">Reset password</h1>
  <?php if (!empty($message)): ?><p class="note"><?= e($message) ?></p><?php endif; ?>
  <?php if (!empty($error)): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/forgot" class="stack">
    <?= \App\Security\Csrf::field() ?>
    <label>Email <input type="email" name="email" required></label>
    <button class="btn" type="submit">Send reset link</button>
  </form>
</section>
