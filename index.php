<?php

include_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/src/utils.php';

$mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
$settingsCollection = $mongoClient->selectDatabase($_ENV['DB_NAME'])->selectCollection('settings');

// 1. Get default ID send by save_settings.php
$selectedEventId = array_get_default($_GET, 'last_id', '');

// Set default to last event if ID is empty
if (empty($selectedEventId)) {
    $latestEvent = $settingsCollection->findOne([], ['sort' => ['updated_at' => -1]]);
    if ($latestEvent) {
        $selectedEventId = (string) $latestEvent['_id'];
    }
}

$allEvents = $settingsCollection->find([], ['sort' => ['event_name' => 1]]);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Saisie Ticket POS80</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f4f4f9;
        }

        .container {
            max-width: 600px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
        }

        /* Search Autocomplete Widget Layout */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
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
            max-width: 400px;
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

        /* Dynamic Product Grid CSS Layouts */
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
            max-width: 100px;
        }

        button {
            padding: 10px 15px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button.btn-add {
            background: #28a745;
        }

        button.btn-remove {
            background: #dc3545;
        }

        .submit-btn {
            width: 100%;
            margin-top: 20px;
            font-size: 16px;
        }
    </style>
</head>

<body>

    <div id="eventModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 25px; border-radius: 8px; width: 100%; max-width: 450px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <h3 style="margin-top: 0; border-bottom: 2px solid #2ecc71; padding-bottom: 10px;">Créer un nouvel Événement</h3>

            <form action="src/save_settings.php" method="POST">
                <div style="margin-bottom: 12px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Code Pays</label>
                    <input type="text" name="country_code" value="261" maxlength="3" required pattern="\d{3}" style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Code Année</label>
                    <input type="text" name="year_code" value="<?= date('y') ?>" maxlength="2" required pattern="\d{2}" style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">ID de l'Événement</label>
                    <input type="text" name="event_id" placeholder="Ex: 6" maxlength="1" required pattern="\d{1}" style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Nom de l'Événement</label>
                    <input type="text" name="event_name" placeholder="Ex: Vokatra 06/2026" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeEventModal()" style="padding: 8px 15px; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer;">Annuler</button>
                    <button type="submit" style="padding: 8px 15px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <h2>Fandraisana vokatra</h2>
        <form action="src/print.php" method="POST" target="print_popup" onsubmit="openPrintPopup();">

            <div class="event-selector-bar" style="background: #ecf0f1; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <label for="current_event_select" style="font-weight: bold;">Événement actif :</label>

                <select id="current_event_select" name="global_event_setting_id" required style="padding: 8px; font-size: 14px; min-width: 250px;">
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

                <button type="button" onclick="openEventModal()" style="padding: 8px 12px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                    + Nouveau
                </button>
            </div>
            <div class="search-container">
                <label for="user-search">Rechercher un Mpivavaka :</label>
                <input type="text" id="user-search" name="user_name" class="search-input" autocomplete="off" placeholder="Tapez les premières lettres...">
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
                <button type="button" class="btn-add" onclick="addRow()">+ Hanampy vokatra</button>
            </div>

            <button type="submit" style="width: 75%; margin-top: 20px; font-size: 16px;">Hamoahana rosia</button>
            <button type="reset" style="width: 20%; margin-top: 20px; font-size: 16px;">Manaraka</button>
        </form>
    </div>

    <script>
        // Track dynamic field injection index values
        let rowIdx = 1;

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('user-search');
            const suggestionsList = document.getElementById('suggestions-list');
            const hiddenIdInput = document.getElementById('selected-user-id');
            const selectionDisplay = document.getElementById('selection-display');
            const confirmedName = document.getElementById('confirmed-name');

            // Listen for keyboard entry input changes on target selection string field
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();

                // Terminate querying runtime execution if buffer length threshold is unmet
                if (query.length < 2) {
                    suggestionsList.style.display = 'none';
                    return;
                }

                // Query local microservice repository endpoints
                fetch('src/database/getUser.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        suggestionsList.innerHTML = ''; // Reset rendering element nodes

                        if (data.length === 0) {
                            suggestionsList.innerHTML = '<div class="suggestion-empty">Aucun résultat trouvé</div>';
                            suggestionsList.style.display = 'block';
                            return;
                        }

                        // Map array mutations directly into view elements
                        data.forEach(user => {
                            const row = document.createElement('div');
                            row.className = 'suggestion-item';
                            row.textContent = user.name;

                            // Handle click interaction logging mechanisms securely locking selections
                            row.addEventListener('click', function() {
                                searchInput.value = user.name;
                                hiddenIdInput.value = user.id;

                                confirmedName.textContent = user.name + " (ID: " + user.id + ")";
                                selectionDisplay.style.display = 'block';
                                suggestionsList.style.display = 'none';

                                console.log("Selected user locked down for rendering:", hiddenIdInput.value);
                            });

                            suggestionsList.appendChild(row);
                        });

                        suggestionsList.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('An error occurred during dataset mapping retrieval operations:', error);
                    });
            });

            // Dismiss active tracking node displays if focus is dropped or redirection triggers outside targeted zones
            document.addEventListener('click', function(e) {
                if (e.target !== searchInput && e.target !== suggestionsList) {
                    suggestionsList.style.display = 'none';
                }
            });
        });

        /**
         * Appends an additional product input item fields structural template row grid
         */
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

        /**
         * Destroys target structural item array components unless it is the single remaining instance row
         * @param {HTMLElement} btn - Element tracking button trigger targets
         */
        function removeRow(btn) {
            const rows = document.querySelectorAll('.product-row');
            if (rows.length > 1) {
                btn.parentElement.remove();
            }
        }

        function openPrintPopup() {
            // Define the dimensions and features of the POS80 print window
            const width = window.screen.width - (window.screen.width / 4);
            const height = window.screen.height - (window.screen.height / 4);
            const left = (window.screen.width / 2) - (width / 2);
            const top = (window.screen.height / 2) - (height / 2);

            // Open a blank window with the correct name and specifications before the form submits into it
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