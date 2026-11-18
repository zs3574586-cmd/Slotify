<h1>Book your slot</h1>
<p class="card"><strong><?= h(fmt_date($slot['starts_at'])) ?></strong>, <?= h(fmt_time($slot['starts_at'])) ?> – <?= h(fmt_time($slot['ends_at'])) ?></p>
<?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
<form method="post" class="card form">
  <?= csrf_field() ?><input type="hidden" name="slot" value="<?= (int)$slot['id'] ?>">
  <label>Name <input name="name" required maxlength="100" value="<?= h($old['name']) ?>"></label>
  <label>Email <input type="email" name="email" required value="<?= h($old['email']) ?>"></label>
  <label>Note (optional) <textarea name="note" rows="3" maxlength="500"><?= h($old['note']) ?></textarea></label>
  <button>Confirm booking</button>
</form>
