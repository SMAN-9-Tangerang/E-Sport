<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<h1>User Dashboard</h1>

<h2>Your Teams</h2>
<?php if (!empty($teams)): ?>
    <ul>
        <?php foreach ($teams as $team): ?>
            <li>
                <strong><?= esc($team['name']) ?></strong> (Tag: <?= esc($team['tag']) ?>)
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>You are not a member of any teams yet.</p>
<?php endif; ?>

<h2>Pending Membership Requests</h2>
<?php if (!empty($pending_requests)): ?>
    <ul>
        <?php foreach ($pending_requests as $request): ?>
            <li>
                Team ID: <?= esc($request['team_id']) ?> - Status: <?= esc($request['status']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No pending membership requests.</p>
<?php endif; ?>

<p><a href="/auth/logout">Logout</a></p>
<?= $this->endSection() ?>
