<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';

$stmt = $pdo->query("
    SELECT ID, title, client, siteName, reviewDate, status
    FROM rapport
    ORDER BY ID DESC
");

$rapporter = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Rapporter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            border: 1px solid #333;
            text-decoration: none;
            color: #000;
            background: #f5f5f5;
        }

        .button:hover,
        .button:focus {
            background: #e9e9e9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            border: 1px solid #ccc;
            padding: 10px;
            vertical-align: top;
        }

        th {
            background: #f3f3f3;
        }

        .empty-state {
            padding: 16px;
            border: 1px solid #ccc;
            background: #fafafa;
        }

        .actions a {
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <h1>Rapporter</h1>
        <a class="button" href="create-rapport.php">Skapa rapport</a>
    </div>

    <?php if (empty($rapporter)): ?>
        <div class="empty-state">
            <p>Det finns inga rapporter ännu.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titel</th>
                    <th>Kund</th>
                    <th>Webbplats</th>
                    <th>Granskningsdatum</th>
                    <th>Status</th>
                    <th>Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rapporter as $rapport): ?>
                    <tr>
                        <td><?= (int)$rapport['ID'] ?></td>
                        <td><?= htmlspecialchars($rapport['title']) ?></td>
                        <td><?= htmlspecialchars($rapport['client']) ?></td>
                        <td><?= htmlspecialchars($rapport['siteName']) ?></td>
                        <td><?= htmlspecialchars($rapport['reviewDate']) ?></td>
                        <td><?= htmlspecialchars($rapport['status']) ?></td>
                        <td class="actions">
                            <a href="view-rapport.php?id=<?= (int)$rapport['ID'] ?>">Öppna</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>