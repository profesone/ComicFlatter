<section class="section jobs-intro">
  <div class="wrap">
    <p class="kicker">Free</p>
    <h1 class="display">Creator board</h1>
    <p>Studios and creators post paid comic work. Anyone can read. Posting needs an account.</p>
    <?php if ($posts): ?>
      <ul class="role-list">
        <?php foreach ($posts as $post): ?>
          <li>
            <span><?= e($post['role']) ?></span>
            <strong><?= e($post['title']) ?></strong>
            <em><?= e($post['body']) ?></em>
            <small><?= e($post['name'] ?: $post['email']) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No posts yet.</p>
    <?php endif; ?>
  </div>
</section>
<section class="section jobs-pricing">
  <div class="wrap">
    <?php if ($user): ?>
      <h2 class="display">Post a role</h2>
      <?php if (!empty($error)): ?><p class="hint"><?= e($error) ?></p><?php endif; ?>
      <form method="post" action="/jobs" class="card board-form">
        <?= \App\Security\Csrf::field() ?>
        <label>Role
          <select name="role" required>
            <option>Letterer</option>
            <option>Flatter</option>
            <option>Colorist</option>
            <option>Other</option>
          </select>
        </label>
        <label>Title
          <input name="title" maxlength="160" required>
        </label>
        <label>Description
          <textarea name="body" rows="5" maxlength="4000" required></textarea>
        </label>
        <button class="btn" type="submit">Publish</button>
      </form>
    <?php else: ?>
      <h2 class="display">Post a role</h2>
      <p>Reading is open. Log in to publish a listing. There is no fee.</p>
      <a class="btn" href="/login">Log in to post</a>
    <?php endif; ?>
  </div>
</section>
