<?php
include_once __DIR__ . '/../vendor/autoload.php';

$mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
$settingsCollection = $mongoClient->selectDatabase($_ENV['DB_NAME'])->selectCollection('settings');
$vokatraCollection = $mongoClient->selectDatabase($_ENV['DB_NAME'])->selectCollection('vokatra');

// 1. Get default ID sent by save_settings.php
$selectedEventId = array_get_default($_GET, 'last_id', '');

// Set default to last event if ID is empty
if (empty($selectedEventId)) {
    $latestEvent = $settingsCollection->findOne([], ['sort' => ['updated_at' => -1]]);
    if ($latestEvent) {
        $selectedEventId = (string) $latestEvent['_id'];
    }
}

$allEvents = $settingsCollection->find([], ['sort' => ['event_name' => 1]]);

// Fetch only the 5 latest printed records for the live sidebar widget
$sidebarLimit = 5;
$latestScansCursor = $vokatraCollection->find([], [
    'sort'  => ['printed_at' => -1],
    'limit' => $sidebarLimit
]);
$latestScans = $latestScansCursor->toArray();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Saisie Ticket POS80</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 20px;
            background: #f4f4f9;
            color: #333;
        }

        .main-layout {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            max-width: 1100px;
            margin: 0 auto;
        }

        .container {
            flex: 2;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .sidebar {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-top: 4px solid #3498db;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .search-container {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .suggestions-dropdown {
            position: absolute;
            width: 100%;
            background: white;
            border: 1px solid #ccc;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0 0 4px 4px;
        }

        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }

        .suggestion-item:hover {
            background-color: #f5f5f5;
        }

        .suggestion-empty {
            padding: 10px;
            color: #888;
            font-style: italic;
        }

        .selection-panel {
            display: none;
            padding: 12px;
            background-color: #e2f0d9;
            border: 1px solid #70ad47;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 20px;
        }

        .selection-status {
            color: #385723;
            font-weight: bold;
        }

        .selection-text {
            color: #385723;
        }

        .product-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .product-row input {
            flex: 1;
        }

        .qty-input {
            max-width: 70px;
        }

        .price-input {
            max-width: 110px;
        }

        button {
            padding: 10px 15px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        button.btn-add {
            background: #28a745;
        }

        button.btn-remove {
            background: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div id="eventModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 25px; border-radius: 8px; width: 100%; max-width: 450px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <h3 style="margin-top: 0; border-bottom: 2px solid #2ecc71; padding-bottom: 10px;">Créer un nouvel Événement</h3>

            <form action="controllers/save_settings.php" method="POST">
                <div style="margin-bottom: 12px;">
                    <label>Code Pays</label>
                    <input type="text" name="country_code" value="261" maxlength="3" required pattern="\d{3}">
                </div>
                <div style="margin-bottom: 12px;">
                    <label>Code Année</label>
                    <input type="text" name="year_code" value="<?= date('y') ?>" maxlength="2" required pattern="\d{2}">
                </div>
                <div style="margin-bottom: 12px;">
                    <label>ID de l'Événement</label>
                    <input type="text" name="event_id" placeholder="Ex: 6" maxlength="1" required pattern="\d{1}">
                </div>
                <div style="margin-bottom: 20px;">
                    <label>Nom de l'Événement</label>
                    <input type="text" name="event_name" placeholder="Ex: Vokatra 06/2026" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeEventModal()" style="background: #e74c3c;">Annuler</button>
                    <button type="submit" style="background: #2ecc71;">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="main-layout">

        <div class="container">
            <h2>Fandraisana vokatra</h2>
            <form action="controllers/print.php" method="POST" target="print_popup" onsubmit="openPrintPopup();">

                <div style="background: #ecf0f1; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                    <label for="current_event_select" style="margin-bottom: 0;">Événement actif :</label>

                    <select id="current_event_select" name="global_event_setting_id" required style="flex: 1; min-width: 200px;">
                        <option value="">-- Choisir un événement --</option>
                        <?php foreach ($allEvents as $event): ?>
                            <?php $eventIdString = (string) $event['_id']; ?>
                            <option value="<?= $eventIdString ?>"
                                data-country="<?= $event['country_code'] ?>"
                                data-year="<?= $event['year_code'] ?>"
                                data-eventid="<?= $event['event_id'] ?>"
                                <?= ($eventIdString === $selectedEventId) ? 'selected' : '' ?>> <?= htmlspecialchars($event['event_name']) ?> (<?= $event['event_id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="button" onclick="openEventModal()" style="background: #2ecc71;">+ Nouveau</button>
                </div>

                <div class="search-container">
                    <label for="user-search">Rechercher un Mpivavaka :</label>
                    <input type="text" id="user-search" name="user_name" autocomplete="off" placeholder="Tapez les premières lettres...">
                    <input type="hidden" id="selected-user-id" name="user_id">
                    <div id="suggestions-list" class="suggestions-dropdown"></div>
                </div>

                <div id="selection-display" class="selection-panel">
                    <span class="selection-status">✓ Sélectionné :</span>
                    <span id="confirmed-name" class="selection-text"></span>
                </div>

                <div class="form-group">
                    <label>Vokatra :</label>
                    <div id="products_container">
                        <div class="product-row">
                            <input type="text" name="products[0][name]" placeholder="Nom du produit" required>
                            <input type="number" name="products[0][qty]" class="qty-input" placeholder="Qté" min="1" required>
                            <input type="number" step="0.01" name="products[0][price]" class="price-input" placeholder="Prix" required>
                            <button type="button" class="btn-remove" onclick="removeRow(this)">X</button>
                        </div>
                    </div>
                    <button type="button" class="btn-add" onclick="addRow()" style="margin-top: 10px;">+ Hanampy vokatra</button>
                </div>

                <div class="action-buttons">
                    <button type="submit" style="flex: 3; font-size: 15px; background: #007BFF;">Hamoahana rosia</button>
                    <button type="reset" style="flex: 1; font-size: 15px; background: #95a5a6;">Manaraka</button>
                </div>
            </form>
        </div>

        <div class="sidebar">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #f4f4f9; padding-bottom: 10px;">
                <h4 style="margin: 0; color: #2c3e50;">Derniers Scans</h4>
                <a href="history.php" style="font-size: 13px; color: #3498db; text-decoration: none; font-weight: bold;">Voir tout →</a>
            </div>

            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php if (count($latestScans) === 0): ?>
                    <li style="color: #888; font-style: italic; font-size: 13px; text-align: center; padding: 15px 0;">Aucun enregistrement</li>
                <?php else: ?>
                    <?php foreach ($latestScans as $scan): ?>
                        <li style="padding: 10px 0; border-bottom: 1px solid #eee; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; font-weight: bold;">
                                <span style="font-family: monospace;"><?= htmlspecialchars($scan['product_code'] ?? '') ?></span>
                                <span style="color: #2ecc71;"><?= number_format($scan['price'] ?? 0, 0, '.', ' ') ?> MGA</span>
                            </div>
                            <div style="color: #7f8c8d; font-size: 12px; margin-top: 2px; text-transform: uppercase;">
                                <?= htmlspecialchars($scan['name'] ?? '') ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

    </div>

    <script>
        let rowIdx = 1;

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('user-search');
            const suggestionsList = document.getElementById('suggestions-list');
            const hiddenIdInput = document.getElementById('selected-user-id');
            const selectionDisplay = document.getElementById('selection-display');
            const confirmedName = document.getElementById('confirmed-name');

            // Handle typing and searching lookup actions inside the autocomplete box
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();

                if (query.length < 2) {
                    suggestionsList.style.display = 'none';
                    return;
                }

                fetch(`database/getUser.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsList.innerHTML = '';

                        if (data.length === 0) {
                            suggestionsList.innerHTML = '<div class="suggestion-empty">Aucun résultat trouvé</div>';
                            suggestionsList.style.display = 'block';
                            return;
                        }

                        data.forEach(user => {
                            const row = document.createElement('div');
                            row.className = 'suggestion-item';
                            row.textContent = user.name;

                            row.addEventListener('click', function() {
                                searchInput.value = user.name;
                                hiddenIdInput.value = user.id;

                                confirmedName.textContent = `${user.name} (ID: ${user.id})`;
                                selectionDisplay.style.display = 'block';
                                suggestionsList.style.display = 'none';
                            });

                            suggestionsList.appendChild(row);
                        });

                        suggestionsList.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('An error occurred during dataset mapping retrieval operations:', error);
                    });
            });

            // Dismiss dropdown suggestions if clicked outside the target scope elements
            document.addEventListener('click', function(e) {
                if (e.target !== searchInput && e.target !== suggestionsList) {
                    suggestionsList.style.display = 'none';
                }
            });
        });

        function addRow() {
            const container = document.getElementById('products_container');
            const div = document.createElement('div');
            div.className = 'product-row';
            div.innerHTML = `
                <input type="text" name="products[${rowIdx}][name]" placeholder="Nom du produit" required>
                <input type="number" name="products[${rowIdx}][qty]" class="qty-input" placeholder="Qté" min="1" required>
                <input type="number" step="0.01" name="products[${rowIdx}][price]" class="price-input" placeholder="Prix" required>
                <button type="button" class="btn-remove" onclick="removeRow(this)">X</button>
            `;
            container.appendChild(div);
            rowIdx++;
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll('.product-row');
            if (rows.length > 1) {
                btn.parentElement.remove();
            }
        }

        function openPrintPopup() {
            const width = window.screen.width - (window.screen.width / 4);
            const height = window.screen.height - (window.screen.height / 4);
            const left = (window.screen.width / 2) - (width / 2);
            const top = (window.screen.height / 2) - (height / 2);

            window.open('', 'print_popup', `width=${width},height=${height},top=${top},left=${left},status=no,toolbar=no,menubar=no,scrollbars=yes`);
        }

        function openEventModal() {
            document.getElementById('eventModal').style.display = 'flex';
        }

        function closeEventModal() {
            document.getElementById('eventModal').style.display = 'none';
        }

        window.addEventListener('DOMContentLoaded', () => {
            const select = document.getElementById('current_event_select');
            if (select.options.length <= 1) {
                openEventModal();
            }
        });
    </script>
</body>

</html>