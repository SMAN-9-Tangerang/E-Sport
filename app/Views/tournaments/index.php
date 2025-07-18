<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0" style="color: #e43f5a;">🏆 Daftar Turnamen</h2>
    <div>
    <?php if(session()->get('isLoggedIn') && session()->get('role') === 'admin'): ?>
        <a href="/tournaments/create" class="btn btn-primary me-2">Buat Turnamen Baru</a>
        <a href="/tournaments/logout" class="btn btn-danger">Logout</a>
    <?php elseif(session()->get('isLoggedIn') && session()->get('role') === 'user'): ?>
        <a href="/tournaments/dashboard" class="btn btn-secondary me-2">Dashboard Pengguna</a>
        <a href="/tournaments/logout" class="btn btn-danger">Logout</a>
    <?php else: ?>
        <a href="/tournaments/login-user-view" class="btn btn-success me-2">Login User</a>
        <a href="/tournaments/login-admin-view" class="btn btn-info">Login Admin</a>
    <?php endif; ?>
    </div>
</div>

<?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success" role="alert">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if(session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning" role="alert">
        <?= session()->getFlashdata('warning') ?>
    </div>
<?php endif; ?>

<?php if(session()->getFlashdata('error')): ?>
    <div class="alert alert-danger" role="alert">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover" style="--bs-table-bg: #162447; --bs-table-hover-bg: #1f4068;">
                <thead>
                    <tr>
                        <th scope="col">Nama Turnamen</th>
                        <th scope="col">Game</th>
                        <th scope="col">Tipe</th>
                        <th scope="col">Tanggal Mulai</th>
                        <th scope="col" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tournaments)): ?>
                        <?php foreach ($tournaments as $item): 
                            $local = $item['local'];
                            $challonge = $item['challonge'];
                        ?>
                        <tr>
                            <td>
                                <?php if ($local): ?>
                                    <?= esc($local['name']) ?>
                                    <?php if (!empty($local['challonge_url'])): ?>
                                        <a href="<?= esc($local['challonge_url']) ?>" target="_blank" class="badge bg-primary text-decoration-none">Challonge</a>
                                    <?php endif; ?>
                                <?php elseif ($challonge): ?>
                                    <?= esc($challonge['name']) ?>
                                    <a href="<?= esc($challonge['full_challonge_url']) ?>" target="_blank" class="badge bg-primary text-decoration-none">Challonge</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= esc($local['game_name'] ?? ($challonge['game_name'] ?? '-')) ?>
                            </td>
                            <td>
                                <?= ucwords(str_replace('_', ' ', esc($local['tournament_type'] ?? ($challonge['tournament_type'] ?? '-')))) ?>
                            </td>
                            <td>
                                <?php
                                    $startAt = $local['start_at'] ?? ($challonge['start_at'] ?? null);
                                    echo !empty($startAt) ? date('d M Y, H:i', strtotime($startAt)) : 'N/A';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php if ($local): ?>
                                    <?php if(session()->get('role') === 'admin'): ?>
                                        <a href="/tournaments/edit-sync/<?= $local['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="/tournaments/delete-sync/<?= $local['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus turnamen ini?')">Hapus</a>
                                        <a href="/tournaments/teams/<?= $local['id'] ?>" class="btn btn-sm btn-info">Manajemen Tim</a>
                                    <?php endif; ?>
                                    <?php if(!empty($local['challonge_url'])): ?>
                                        <a href="/tournaments/bracket/<?= $local['id'] ?>" class="btn btn-sm btn-success">Lihat Bracket</a>
                                    <?php endif; ?>
                                <?php elseif ($challonge): ?>
                                    <?php if(session()->get('role') === 'admin'): ?>
                                        <!-- Challonge-only tournaments: no local ID, so no edit/delete, only link -->
                                        <span class="text-muted">Tidak dapat diedit</span>
                                    <?php endif; ?>
                                    <a href="<?= esc($challonge['full_challonge_url']) ?>" target="_blank" class="btn btn-sm btn-success">Lihat Bracket</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada turnamen yang dibuat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>