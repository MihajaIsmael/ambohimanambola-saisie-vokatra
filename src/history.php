<?php
include_once __DIR__ . '/../vendor/autoload.php';

$mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
$db = $mongoClient->selectDatabase($_ENV['DB_NAME']);
$vokatraCollection = $db->selectCollection($_ENV['COLLECTION_NAME'] ?? 'vokatra');
$settingsCollection = $db->selectCollection('settings');

// 1. Handle filters and pagination values
$selectedEventId = array_get_default($_GET, 'filter_event_id', '');
$perPage = (int) array_get_default($_GET, 'per_page', 25); // Default to 25 rows
$currentPage = (int) array_get_default($_GET, 'page', 1);
if ($currentPage < 1) $currentPage = 1;

$queryFilter = [];
if (!empty($selectedEventId)) {
    $queryFilter['event_setting_id'] = new MongoDB\BSON\ObjectId($selectedEventId);
}

// 2. Compute total records for this specific query to handle total pages
$totalItems = $vokatraCollection->countDocuments($queryFilter);
$totalPages = ceil($totalItems / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($currentPage > $totalPages) $currentPage = $totalPages;

$skip = ($currentPage - 1) * $perPage;

// 3. Fetch paginated records
$vokatraList = $vokatraCollection->find($queryFilter, [
    'sort'  => ['printed_at' => -1],
    'limit' => $perPage,
    'skip'  => $skip
]);
$allEvents = $settingsCollection->find([], ['sort' => ['event_name' => 1]]);

// 4. Calculate dashboard analytics (Calculated globally based on active event filter)
$totalRevenue = 0;
foreach ($vokatraCollection->find($queryFilter, ['projection' => ['price' => 1]]) as $doc) {
    $totalRevenue += (float) ($doc['price'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion & Historique des Vokatra</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
            color: #333;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        h2 {
            margin: 0;
            color: #2c3e50;
        }

        .btn {
            padding: 10px 15px;
            border-radius: 4px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            border: none;
            display: inline-block;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #2ecc71;
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #3498db;
        }

        .stat-card.revenue {
            border-left-color: #2ecc71;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-top: 5px;
        }

        .filter-zone {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }

        th {
            background-color: #34495e;
            color: white;
            white-space: nowrap;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .mono {
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        /* Pagination styling */
        .pagination-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .pagination-buttons {
            display: flex;
            gap: 5px;
        }

        .page-link {
            padding: 8px 12px;
            background: #f1f2f6;
            text-decoration: none;
            color: #2c3e50;
            border-radius: 4px;
            font-weight: bold;
            font-size: 13px;
        }

        .page-link.active {
            background: #3498db;
            color: white;
        }

        .page-link.disabled {
            background: #e4e7eb;
            color: #a4b0be;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header-bar">
            <h2>Historique & Formatage Dolibarr</h2>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    < Retour Saisie</a>
                        <a href="controllers/export.php?event_id=<?= $selectedEventId ?>" class="btn btn-success">⬇️ Exporter en CSV Dolibarr</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div style="color: #7f8c8d; font-size: 14px;">Total Articles / Lignes Enregistrées</div>
                <div class="stat-number"><?= $totalItems ?> étiquettes</div>
            </div>
            <div class="stat-card revenue">
                <div style="color: #7f8c8d; font-size: 14px;">Valeur totale de la récolte (Sélectionnée)</div>
                <div class="stat-number"><?= number_format($totalRevenue, 0, '.', ' ') ?> MGA</div>
            </div>
        </div>

        <form method="GET" action="history.php" class="filter-zone">
            <div class="filter-group">
                <label style="font-weight: bold;">Filtrer par événement :</label>
                <select name="filter_event_id" style="padding: 8px; font-size: 14px; min-width: 250px;">
                    <option value="">-- Tous les événements --</option>
                    <?php foreach ($allEvents as $event): ?>
                        <?php $stringId = (string) $event['_id']; ?>
                        <option value="<?= $stringId ?>" <?= ($selectedEventId === $stringId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['event_name']) ?> (<?= $event['event_id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <?php if (!empty($selectedEventId)): ?>
                    <a href="history.php?per_page=<?= $perPage ?>" style="color: #e74c3c; font-size: 14px; margin-left: 10px;">Effacer le filtre</a>
                <?php endif; ?>
            </div>

            <div class="filter-group">
                <label for="per_page_select" style="font-size: 13px; color: #7f8c8d;">Lignes par page :</label>
                <select id="per_page_select" name="per_page" onchange="this.form.submit()" style="padding: 6px; font-size: 13px;">
                    <?php foreach ([10, 25, 50, 100] as $limit): ?>
                        <option value="<?= $limit ?>" <?= ($perPage === $limit) ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code-barres (p.barcode)</th>
                        <th>Réf.* (p.ref)</th>
                        <th>Libellé* (p.label)</th>
                        <th>Prix HT / TTC</th>
                        <th>Nanome* (extra.nanome)</th>
                        <th>Produit* (ps.fk_product)</th>
                        <th>Fotoana (extra.fotoana)</th>
                        <th class="text-center">Type / Vente / Achat</th>
                        <th class="text-center">Entrepôt</th>
                        <th class="text-center">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($totalItems === 0): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: #7f8c8d; padding: 30px;">Aucune donnée enregistrée pour cette sélection.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vokatraList as $vokatra): ?>
                            <tr>
                                <td class="mono"><?= htmlspecialchars($vokatra['barcode'] ?? '') ?></td>
                                <td class="mono" style="color: #7f8c8d;"><?= htmlspecialchars($vokatra['product_code'] ?? '') ?></td>
                                <td style="font-weight: bold;"><?= strtoupper(htmlspecialchars($vokatra['name'] ?? '')) ?></td>
                                <td><strong><?= number_format($vokatra['price'] ?? 0, 0, '.', ' ') ?> MGA</strong></td>
                                <td><?= htmlspecialchars($vokatra['user_name'] ?? '') ?></td>
                                <td style="color: #95a5a6; font-style: italic;">type-uniq-id</td>
                                <td><?= htmlspecialchars($vokatra['event_name'] ?? '') ?></td>
                                <td class="text-center" style="color: #7f8c8d;">0 / 1 / 0</td>
                                <td class="text-center">1</td>
                                <td class="text-center" style="font-weight: bold; color: #2ecc71;">1</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <div style="font-size: 13px; color: #7f8c8d;">
                Affichage de <span style="color:#333; font-weight:bold;"><?= min($skip + 1, $totalItems) ?></span> à
                <span style="color:#333; font-weight:bold;"><?= min($skip + $perPage, $totalItems) ?></span> sur
                <span style="color:#333; font-weight:bold;"><?= $totalItems ?></span> lignes
            </div>

            <div class="pagination-buttons">
                <a href="history.php?filter_event_id=<?= $selectedEventId ?>&per_page=<?= $perPage ?>&page=<?= $currentPage - 1 ?>"
                    class="page-link <?= ($currentPage <= 1) ? 'disabled' : '' ?>">« Précédent</a>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2): ?>
                        <a href="history.php?filter_event_id=<?= $selectedEventId ?>&per_page=<?= $perPage ?>&page=<?= $i ?>"
                            class="page-link <?= ($currentPage === $i) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php elseif (abs($i - $currentPage) == 3): ?>
                        <span style="padding: 8px; color: #7f8c8d;">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <a href="history.php?filter_event_id=<?= $selectedEventId ?>&per_page=<?= $perPage ?>&page=<?= $currentPage + 1 ?>"
                    class="page-link <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">Suivant »</a>
            </div>
        </div>
    </div>

</body>

</html>