<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<h1>Manajemen Tim: <?= esc($team['name']) ?></h1>
<a href="/tournaments/teams/<?= $team['tournament_id'] ?>" class="btn btn-secondary mb-4">&laquo; Kembali ke Manajemen Tim</a>

<div class="card">
    <div class="card-header">
        Anggota Tim
    </div>
    <div class="card-body">
        <?php if (empty($members)): ?>
            <p>Belum ada anggota di tim ini.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID Pengguna</th>
                        <th>Status</th>
                        <th>Peran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= esc($member['user_id']) ?></td>
                        <td><?= esc($member['status']) ?></td>
                        <td><?= esc($member['role']) ?></td>
                        <td>
                            <?php if ($member['role'] !== 'leader'): ?>
                            <form action="/tournaments/teams/remove_member/<?= esc($team['id']) ?>/<?= esc($member['id']) ?>" method="post" style="display:inline;">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus anggota ini?')">Keluarkan</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        Undang Pengguna Baru
    </div>
    <div class="card-body">
        <form action="/tournaments/teams/invite/<?= esc($team['id']) ?>" method="post">
            <div class="input-group">
                <input type="text" name="username" class="form-control" placeholder="Masukkan username pengguna" required>
                <button class="btn btn-primary" type="submit">Undang</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>