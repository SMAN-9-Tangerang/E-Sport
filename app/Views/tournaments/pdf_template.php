<!DOCTYPE html>
<html>
<head>
    <title>Daftar Tim</title>
    <style>
        body { font-family: sans-serif; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h2>Daftar Tim - <?= esc($tournament['name']) ?></h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Tim</th>
                <th>Tag</th>
                <th>ID Partisipan Challonge</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($teams as $team): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= esc($team['name']) ?></td>
                <td><?= esc($team['tag']) ?></td>
                <td><?= esc($team['challonge_participant_id'] ?? 'N/A') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>