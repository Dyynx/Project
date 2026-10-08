<div class="container py-5"><div class="auth-card mx-auto">
<h2 class="fw-bold">Create account</h2><p class="text-secondary">Mulai tracking anime dan manga kamu.</p>
<form method="post">
<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="register">
<label class="form-label">Username</label><input name="username" class="form-control mb-3" pattern="[A-Za-z0-9_]{3,50}" required>
<label class="form-label">Email</label><input name="email" type="email" class="form-control mb-3" required>
<label class="form-label">Password</label><input name="password" type="password" class="form-control mb-3" minlength="6" required>
<button class="btn btn-primary w-100">Register</button>
</form>
</div></div>
