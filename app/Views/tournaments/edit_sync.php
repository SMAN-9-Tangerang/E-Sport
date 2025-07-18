<!-- app/Views/tournaments/edit_sync.php -->
<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <h2>Edit & Sync Tournament</h2>

    <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif; ?>
    <?php if (session('success')): ?>
        <div class="alert alert-success"><?= esc(session('success')) ?></div>
    <?php endif; ?>

    <form action="<?= site_url('/tournaments/editSync/' . esc($tournament['id'])) ?>" method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="name" class="form-label">Tournament Name</label>
            <input type="text" class="form-control" id="name" name="name"
                   value="<?= old('name', $tournament['name'] ?? $challonge['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="url" class="form-label">Challonge URL</label>
            <input type="text" class="form-control" id="url" name="url"
                   value="<?= old('url', $tournament['url'] ?? $challonge['url'] ?? '') ?>" required>
            <div class="form-text">Unique Challonge URL (e.g. my-tourney-2024)</div>
        </div>
        <div class="mb-3">
            <label for="game_name" class="form-label">Game Name</label>
            <input type="text" class="form-control" id="game_name" name="game_name"
                   value="<?= old('game_name', $tournament['game_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="tournament_type" class="form-label">Tournament Type</label>
            <select class="form-select" id="tournament_type" name="tournament_type" required>
                <?php
                $types = [
                    'single elimination' => 'Single Elimination',
                    'double elimination' => 'Double Elimination',
                    'round robin' => 'Round Robin',
                    'swiss' => 'Swiss'
                ];
                $selectedType = old('tournament_type', $tournament['tournament_type'] ?? $challonge['tournament_type'] ?? 'single elimination');
                foreach ($types as $key => $label): ?>
                    <option value="<?= esc($key) ?>" <?= $selectedType == $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?= old('description', $tournament['description'] ?? $challonge['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="start_at" class="form-label">Start At</label>
            <input type="datetime-local" class="form-control" id="start_at" name="start_at"
                   value="<?= old('start_at', isset($tournament['start_at']) ? date('Y-m-d\TH:i', strtotime($tournament['start_at'])) : (isset($challonge['start_at']) ? date('Y-m-d\TH:i', strtotime($challonge['start_at'])) : '')) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Update & Sync</button>
        <a href="<?= site_url('/tournaments/list') ?>" class="btn btn-secondary">Cancel</a>
    </form>

    <?php if ($challonge): ?>
        <hr>
        <h5>Challonge Data</h5>
        <ul>
            <li><strong>ID:</strong> <?= esc($challonge['id']) ?></li>
            <li><strong>Name:</strong> <?= esc($challonge['name']) ?></li>
            <li><strong>URL:</strong> <?= esc($challonge['url']) ?></li>
            <li><strong>Type:</strong> <?= esc($challonge['tournament_type']) ?></li>
            <li><strong>Status:</strong> <?= esc($challonge['state']) ?></li>
        </ul>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>