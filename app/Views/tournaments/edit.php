<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
    <h2>Edit Turnamen</h2>
    <form id="editTournamentForm">
        <div class="mb-3">
            <label for="name" class="form-label">Nama Turnamen</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= esc($tournament['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="url" class="form-label">URL Turnamen (unik)</label>
            <input type="text" id="url" name="url" class="form-control" value="<?= esc($tournament['url']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="game_name" class="form-label">Game</label>
            <input type="text" id="game_name" name="game_name" class="form-control" value="<?= esc($tournament['game_name']) ?>">
        </div>
        <div class="mb-3">
            <label for="start_at" class="form-label">Tanggal Mulai</label>
            <input type="datetime-local" id="start_at" name="start_at" class="form-control" value="<?= isset($tournament['start_at']) ? date('Y-m-d\TH:i', strtotime($tournament['start_at'])) : '' ?>">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <textarea id="description" name="description" class="form-control"><?= esc($tournament['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="tournament_type" class="form-label">Tipe Turnamen</label>
            <select id="tournament_type" name="tournament_type" class="form-select">
                <option value="single elimination" <?= $tournament['tournament_type'] == 'single elimination' ? 'selected' : '' ?>>Single Elimination</option>
                <option value="double elimination" <?= $tournament['tournament_type'] == 'double elimination' ? 'selected' : '' ?>>Double Elimination</option>
                <option value="round robin" <?= $tournament['tournament_type'] == 'round robin' ? 'selected' : '' ?>>Round Robin</option>
                <option value="swiss" <?= $tournament['tournament_type'] == 'swiss' ? 'selected' : '' ?>>Swiss</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Turnamen</button>
        <a href="/tournaments" class="btn btn-secondary">Batal</a>
    </form>
    <script>
    document.getElementById('editTournamentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = <?= (int)$tournament['id'] ?>;
        const data = {
            name: document.getElementById('name').value,
            url: document.getElementById('url').value,
            game_name: document.getElementById('game_name').value,
            start_at: document.getElementById('start_at').value,
            description: document.getElementById('description').value,
            tournament_type: document.getElementById('tournament_type').value
        };
        const response = await fetch('/tournaments/update/' + id, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        if (response.ok) {
            window.location.href = '/tournaments';
        } else {
            const res = await response.json();
            alert('Gagal update turnamen: ' + (res.messages?.error || ''));
        }
    });
    </script>
<?= $this->endSection() ?>