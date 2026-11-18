<h1>Booking <?= $b['status'] === 'confirmed' ? 'confirmed' : 'cancelled' ?></h1>
<div class="card">
  <p><strong><?= h(fmt_date($b['starts_at'])) ?></strong>, <?= h(fmt_time($b['starts_at'])) ?> – <?= h(fmt_time($b['ends_at'])) ?></p>
  <p><?= h($b['name']) ?> · <?= h($b['email']) ?></p>
  <?php if ($b['status'] === 'confirmed' && $b['starts_at'] > now()): ?>
    <p class="muted">Bookmark this page — this private link is how you cancel.</p>
    <form method="post" action="<?= url('cancel') ?>" onsubmit="return confirm('Cancel this booking?')">
      <?= csrf_field() ?><input type="hidden" name="t" value="<?= h($b['token']) ?>">
      <button class="danger">Cancel booking</button>
    </form>
  <?php endif; ?>
</div>
