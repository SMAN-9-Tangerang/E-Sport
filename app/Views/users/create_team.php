<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<h1>Create New Team</h1>

<?php if(session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form action="/tournaments/store-user-team" method="post">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="name">Team Name</label>
        <input type="text" name="name" id="name" class="form-control" required />
    </div>
    <div class="form-group mb-3">
        <label for="tag">Team Tag</label>
        <input type="text" name="tag" id="tag" class="form-control" />
    </div>
    <button type="submit" class="btn btn-primary">Create Team</button>
</form>

<p><a href="/tournaments/user-dashboard">Back to Dashboard</a></p>
<?= $this->endSection() ?>
