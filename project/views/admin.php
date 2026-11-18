<h1>Dashboard</h1>
<div class="stats">
  <div class="card"><b><?= (int)$stats['upcoming'] ?></b>Upcoming bookings</div>
  <div class="card"><b><?= (int)$stats['open'] ?></b>Open slots</div>
  <div class="card"><b><?= (int)$stats['cancelled'] ?></b>Cancelled</div>
</div>

<h2>Add availability</h2>
<form method="post" action="<?= url('admin/slots') ?>" class="card inline">
  <?= csrf_field() ?>
  <label>Date <input type="date" name="date" required min="<?= date('Y-m-d') ?>"></label>
  <label>From <input type="time" name="from" value="09:00" required></label>
  <label>To <input type="time" name="to" value="17:00" required></label>
  <label>Slot (min) <input type="number" name="len" value="60" min="5" max="480" required></label>
  <button>Generate slots</button>
</form>

<h2>Bookings</h2>
<div class="card scroll"><table>
<tr><th>When</th><th>Customer</th><th>Note</th><th>Status</th><th></th></tr>
<?php foreach ($bookings as $b): ?>
<tr class="<?= h($b['status']) ?>">
  <td><?= h(fmt_date($b['starts_at'])) ?> <?= h(fmt_time($b['starts_at'])) ?></td>
  <td><?= h($b['name']) ?><br><small><?= h($b['email']) ?></small></td>
  <td><?= h($b['note']) ?></td><td><?= h($b['status']) ?></td>
  <td><?php if ($b['status'] === 'confirmed'): ?>
    <form method="post" action="<?= url('admin/booking-cancel') ?>" onsubmit="return confirm('Cancel this booking?')">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="danger sm">Cancel</button></form>
  <?php endif; ?></td>
</tr>
<?php endforeach; if (!$bookings): ?><tr><td colspan="5">No bookings yet.</td></tr><?php endif; ?>
</table></div>

<h2>Upcoming slots</h2>
<div class="card scroll"><table>
<tr><th>When</th><th>State</th><th></th></tr>
<?php foreach ($slots as $s): ?>
<tr><td><?= h(fmt_date($s['starts_at'])) ?> <?= h(fmt_time($s['starts_at'])) ?>–<?= h(fmt_time($s['ends_at'])) ?></td>
  <td><?= $s['taken'] ? 'Booked' : 'Open' ?></td>
  <td><?php if (!$s['taken']): ?>
    <form method="post" action="<?= url('admin/slot-delete') ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="danger sm">Delete</button></form>
  <?php endif; ?></td></tr>
<?php endforeach; if (!$slots): ?><tr><td colspan="3">No upcoming slots. Add some above.</td></tr><?php endif; ?>
</table></div>
