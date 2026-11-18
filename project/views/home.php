<h1><?= h(cfg('tagline')) ?></h1>
<p class="muted">Pick an available time. Times shown in <?= h(cfg('timezone')) ?>.</p>
<?php if (!$days): ?><p class="card">No open slots right now — please check back soon.</p><?php endif; ?>
<?php foreach ($days as $date => $slots): ?>
  <section class="card"><h2><?= h(fmt_date($date)) ?></h2>
  <div class="slots">
  <?php foreach ($slots as $s): ?>
    <a class="slot" href="<?= url('book', ['slot' => $s['id']]) ?>"><?= h(fmt_time($s['starts_at'])) ?> – <?= h(fmt_time($s['ends_at'])) ?></a>
  <?php endforeach; ?>
  </div></section>
<?php endforeach; ?>
