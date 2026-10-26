<section class="section">
  <div class="wrap account-page">
    <p class="kicker">Account</p>
    <h1 class="display">Profile</h1>
    <?php if (!empty($saved)): ?><p class="hint"><?= e($saved) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="hint"><?= e($error) ?></p><?php endif; ?>

    <form method="post" action="/account" class="card board-form">
      <?= \App\Security\Csrf::field() ?>
      <label>Name
        <input name="name" maxlength="120" value="<?= e($user['name'] ?? '') ?>">
      </label>
      <label>Email
        <input type="email" name="email" required value="<?= e($user['email']) ?>">
      </label>
      <label>New password
        <input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep the current password">
      </label>
      <label>Current password
        <input type="password" name="current_password" required autocomplete="current-password">
      </label>
      <button class="btn" type="submit">Save profile</button>
    </form>

    <h2 class="display">Purchases</h2>
    <?php if ($purchases): ?>
      <ul class="role-list">
        <?php foreach ($purchases as $purchase): ?>
          <li>
            <span>$4.99</span>
            <strong>Comic Flatter</strong>
            <em><?= e($purchase['status']) ?> · <?= e($purchase['created_at']) ?></em>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No purchases yet.</p>
    <?php endif; ?>
    <p>If you delete this account and later create it again with the same email, these purchases stay on the account.</p>

    <h2 class="display">Delete account</h2>
    <form method="post" action="/account/delete" class="card board-form">
      <?= \App\Security\Csrf::field() ?>
      <p>This signs you out and closes the login. Purchase history stays, so the same email can open it again.</p>
      <label>Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <label>Type delete to confirm
        <input name="confirm" required autocomplete="off">
      </label>
      <button class="btn secondary" type="submit">Delete account</button>
    </form>
  </div>
</section>
