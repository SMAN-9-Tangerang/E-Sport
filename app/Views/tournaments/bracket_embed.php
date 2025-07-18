<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Bracket Turnamen: <?= esc($tournament['name']) ?></h2>
        <a href="/tournaments" class="btn btn-secondary"> &laquo; Kembali</a>
    </div>

    <?php if (!empty($tournament['challonge_url'])):
        // Mengubah URL lengkap menjadi URL embed
        $embed_url = str_replace('https://challonge.com/', '', $tournament['challonge_url']);
    ?>
    <div class="iframe-container" style="position: relative; overflow: hidden; width: 100%; padding-top: 75%;">
        <iframe 
            src="//challonge.com/<?= esc($embed_url, 'attr') ?>/module" 
            width="100%" 
            height="100%" 
            frameborder="0" 
            scrolling="auto" 
            allowtransparency="true"
            style="position: absolute; top: 0; left: 0; bottom: 0; right: 0;">
        </iframe>
    </div>
    <div class="mt-2">
        <p>Tidak dapat melihat bracket? <a href="<?= esc($tournament['challonge_url'], 'attr') ?>" target="_blank">Buka di tab baru</a>.</p>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        URL Bracket untuk turnamen ini tidak tersedia.
    </div>
    <?php endif; ?>
<?= $this->endSection() ?>