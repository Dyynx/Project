<div class="container py-5"><div class="auth-card mx-auto">
<h2 class="fw-bold">Reset password</h2><p class="text-secondary">Masukkan email akunmu.</p>
<form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="forgot">
<input name="email" type="email" class="form-control mb-3" required><button class="btn btn-primary w-100">Generate Reset Link</button></form>
<?php if (!empty($_SESSION['dev_reset_url'])): ?><div class="alert alert-warning mt-4 small"><strong>Development mode:</strong><br><a href="<?= e($_SESSION['dev_reset_url']) ?>">Open reset link</a></div><?php endif; ?>
</div></div>
