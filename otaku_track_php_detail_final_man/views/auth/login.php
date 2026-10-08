<div class="container py-5"><div class="auth-card mx-auto">
<h2 class="fw-bold">Welcome back</h2><p class="text-secondary">Login ke Otaku Track.</p>
<form method="post">
<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="login">
<label class="form-label">Username / Email</label><input name="login" class="form-control mb-3" required>
<label class="form-label">Password</label><input name="password" type="password" class="form-control mb-3" required>
<button class="btn btn-primary w-100">Login</button>
</form>
<div class="d-flex justify-content-between mt-3 small"><a href="<?= url('register') ?>">Buat akun</a><a href="<?= url('forgot') ?>">Forgot password?</a></div>
</div></div>
