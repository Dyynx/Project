<div class="container py-5"><div class="auth-card mx-auto">
<h2 class="fw-bold">New password</h2>
<form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="token" value="<?= e($token) ?>">
<input name="password" type="password" class="form-control mb-3" minlength="6" required><button class="btn btn-primary w-100">Change Password</button></form>
</div></div>
