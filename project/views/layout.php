<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title ? "$title · " : '') . h(cfg('name')) ?></title>
<link rel="stylesheet" href="style.css">
</head><body>
<header><div class="wrap bar">
  <a class="brand" href="<?= url('home') ?>"><?= h(cfg('name')) ?></a>
  <nav><a href="<?= url('home') ?>">Book</a>
  <?php if (is_admin()): ?><a href="<?= url('admin') ?>">Dashboard</a><a href="<?= url('admin/logout') ?>">Log out</a>
  <?php else: ?><a href="<?= url('admin/login') ?>">Admin</a><?php endif; ?></nav>
</div></header>
<main class="wrap">
<?php if ($flash): ?><div class="flash <?= h($flash[1]) ?>"><?= h($flash[0]) ?></div><?php endif; ?>
<?= $content ?>
</main>
</body></html>
