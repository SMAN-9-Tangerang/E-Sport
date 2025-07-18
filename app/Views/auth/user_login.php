username: form.username.value,

<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<h2>User Login</h2>
<div id="login-error" class="alert alert-danger d-none"></div>
<form id="user-login-form" autocomplete="off">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Login</button>
</form>
<script>
document.getElementById('user-login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = {
        password: form.password.value
    };
    const csrfToken = form.querySelector('input[name=<?= csrf_token() ?>]').value;
    try {
        const res = await fetch('/tournaments/login-user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok && result.status === 'success') {
            window.location.href = '/tournaments'; // redirect after login
        } else {
            document.getElementById('login-error').classList.remove('d-none');
            document.getElementById('login-error').textContent = result.messages?.error || result.message || 'Login failed';
        }
    } catch (err) {
        document.getElementById('login-error').classList.remove('d-none');
        document.getElementById('login-error').textContent = 'Network error';
    }
});
</script>
<?= $this->endSection() ?>
