<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
    <h2>Buat Turnamen Baru</h2>
    <form id="tournamentForm">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="name" class="form-label">Nama Turnamen</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <!-- <div class="mb-3">
            <label for="url" class="form-label">URL (unik, tanpa spasi)</label>
            <input type="text" name="url" class="form-control" required>
        </div> -->
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label for="game_name" class="form-label">Game</label>
            <input type="text" name="game_name" class="form-control">
        </div>
        <div class="mb-3">
            <label for="tournament_type" class="form-label">Tipe Turnamen</label>
            <select name="tournament_type" class="form-select">
                <option value="single elimination">Single Elimination</option>
                <option value="double elimination">Double Elimination</option>
                <option value="round robin">Round Robin</option>
                <option value="swiss">Swiss</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="start_at" class="form-label">Waktu Mulai (opsional)</label>
            <input type="datetime-local" name="start_at" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Simpan Turnamen</button>
        <a href="/tournaments" class="btn btn-secondary">Batal</a>
    </form>
    <script>
    document.getElementById('tournamentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const data = {
            name: form.name.value,
            // url: form.url.value,
            description: form.description.value,
            game_name: form.game_name.value,
            tournament_type: form.tournament_type.value,
            start_at: form.start_at.value ? form.start_at.value : null
        };
        const csrfToken = form.querySelector('input[name=<?= csrf_token() ?>]').value;
        const response = await fetch('/tournaments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok) {
            alert('Turnamen berhasil dibuat!');
            window.location.href = '/tournaments';
        } else {
            alert('Gagal membuat turnamen: ' + JSON.stringify(result));
        }
    });
    </script>
<?= $this->endSection() ?>