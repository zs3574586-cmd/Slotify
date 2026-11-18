<h1>Admin login</h1>
<form method="post" action="<?= url('admin/login') ?>" class="card form">
  <?= csrf_field() ?>
  <label>Password <input type="password" name="password" required autofocus></label>
  <button>Log in</button>
</form>
