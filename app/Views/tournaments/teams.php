<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<h2 class="mb-3">Manajemen Tim: <span style="color: #e43f5a;"><?= esc($tournament['name']) ?></span></h2>
<a href="/tournaments" class="btn btn-secondary mb-4"> &laquo; Kembali ke Daftar Turnamen</a>

<div class="card mb-4">
    <div class="card-header text-light">
        Impor & Ekspor Tim
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-6">
                <form action="/tournaments/teams/import/<?= $tournament['id'] ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="excel_file" class="form-label text-light">Impor Tim dari File Excel (.xlsx)</label>
                        <input type="file" name="excel_file" id="excel_file" class="form-control" required accept=".xlsx">
                        <div class="form-text text-light">File harus memiliki kolom A untuk Nama Tim dan kolom B untuk Tag Tim.</div>
                    </div>
                    <button type="submit" class="btn btn-success">Impor Sekarang</button>
                </form>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <p class="mb-2">Ekspor daftar tim saat ini:</p>
                <a href="/tournaments/teams/export/excel/<?= $tournament['id'] ?>" class="btn btn-outline-success">Ekspor ke Excel</a>
                <a href="/tournaments/teams/export/pdf/<?= $tournament['id'] ?>" class="btn btn-outline-danger">Ekspor ke PDF</a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header text-light">
        Tambah Tim Manual
    </div>
    <div class="card-body">
         <form action="/tournaments/teams/store/<?= $tournament['id'] ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3 align-items-center">
                <div class="col-sm-5">
                    <input type="text" name="name" class="form-control" placeholder="Nama Tim" required>
                </div>
                <div class="col-sm-5">
                    <input type="text" name="tag" class="form-control" placeholder="Tag Tim (Opsional)">
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary w-100">Tambah Tim</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header text-light">
        Daftar Tim Terdaftar
    </div>
    <div class="card-body">
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>Nama Tim</th>
                    <th>Tag</th>
                    <th>ID Challonge</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($teams)): ?>
                    <?php foreach ($teams as $team): ?>
                    <tr>
                        <td><?= esc($team['name']) ?></td>
                        <td><?= esc($team['tag']) ?></td>
                        <td><?= esc($team['challonge_participant_id'] ?? 'N/A') ?></td>
                        <td>
                            <a href="/tournaments/teams/delete/<?= $tournament['id'] ?>/<?= $team['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus tim ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Belum ada tim yang terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>