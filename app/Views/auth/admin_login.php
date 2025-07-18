
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<h2>Admin Login</h2>
<form id="adminLoginForm" method="post">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div id="loginError" class="alert alert-danger d-none" role="alert"></div>
    <button type="submit" class="btn btn-primary">Login</button>
</form>
<script>
document.getElementById('adminLoginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = {
        username: form.username.value,
        password: form.password.value
    };
    const response = await fetch(form.action || window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': form.querySelector('input[type=hidden]').value
        },
        body: JSON.stringify(data)
    });
    const result = await response.json();
    if (result.status === 'success') {
        window.location.href = '/tournaments'; // redirect after login
    } else {
        document.getElementById('loginError').classList.remove('d-none');
        document.getElementById('loginError').textContent = result.messages?.error || result.message || 'Login failed';
    }
});
</script>
<?= $this->endSection() ?>
