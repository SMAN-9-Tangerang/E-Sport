<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a2e;
            color: #ffffff;
        }
        .navbar, .card {
            background-color: #162447;
            border: 1px solid #1f4068;
        }
        .btn-primary {
            background-color: #e43f5a;
            border-color: #e43f5a;
        }
        .btn-primary:hover {
            background-color: #b8324f;
            border-color: #b8324f;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url('tournaments/list')?>">
                <img src="<?= base_url('/assets/img/'); ?>256.png" width="150" alt="Smanlanta">
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('tournaments') ?>">Tournaments</a>
                </li>
                <?php if(session()->get('is_admin')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('tournaments/create') ?>">Create Tournament</a>
                </li>
                <?php endif; ?>
                <?php if(session()->get('user_id') && !session()->get('is_admin')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('tournaments/user-dashboard') ?>">Dashboard</a>
                </li>
                <?php endif; ?>
                <?php if(session()->get('user_name')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#">Hello, <?= esc(session()->get('user_name')) ?></a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <div class="container">
        <?= $this->renderSection('content') ?>
    </div>
    <div class="mt-5 p-3 bg-dark text-light" id="footer">
        <footer class="text-center text-light">Website ini dibuat oleh Dzanur</footer>
    </div>
    <script>
        // Remove fixed-bottom if content height exceeds viewport height
        document.addEventListener('DOMContentLoaded', function() {
            var footer = document.getElementById('footer');
            if (document.body.scrollHeight > window.innerHeight) {
                footer.classList.remove('fixed-bottom');
            } else {
                footer.classList.add('fixed-bottom');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
