<!-- views/entry.form.php -->
<?php
/**
 * This file is the entry form for the harvest application.
 * It is loaded by the index.php
 * 
 * @package harvest/vokatra
 * @subpackage views
 * @author Ismael
 * @version 1.0.1
 * @since 2026-07-03
 * 
 * @var array $allEvents
 * @var array $latestScans
 * @var array $newSubscriber
 * @var string $selectedEventId
 * 
 */
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Fandraisana vokatra</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 20px;
            color: #333;
            background: linear-gradient(-45deg, #741288ff, #793e17ff, #d6c81cff, #1c799bff);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite
        }

        .main-layout {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            max-width: 1200px;
            margin: 0 9% 0px auto;
        }

        .container {
            flex: 2;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-top: 4px solid #2ecc71;
        }

        .sidebars-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .sidebar {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-top: 4px solid #3498db;
        }

        .mpivavaka-sidebar {
            border-top: 4px solid #96492bff;
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
            max-width: 50px;
        }

        .price-input {
            max-width: 130px;
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

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }
    </style>
</head>

<body>

    <div id="eventModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 25px; border-radius: 8px; width: 100%; max-width: 450px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <h3 style="margin-top: 0; border-bottom: 2px solid #2ecc71; padding-bottom: 10px;">Fikirana kaody bara</h3>

            <form action="/settings" method="POST">
                <div style="margin-bottom: 12px;">
                    <label>Kaody 3 voalohany</label>
                    <input type="text" name="country_code" value="261" maxlength="3" required pattern="\d{3}">
                </div>
                <div style="margin-bottom: 12px;">
                    <label>Kaody 2 manaraka</label>
                    <input type="text" name="year_code" value="<?= date('y') ?>" maxlength="2" required pattern="\d{2}">
                </div>
                <div style="margin-bottom: 12px;">
                    <label>Kaody 1 farany</label>
                    <input type="text" name="event_id" placeholder="Ex: 6" maxlength="1" required pattern="\d{1}">
                </div>
                <div style="margin-bottom: 20px;">
                    <label>Anaran'ny fotoana</label>
                    <input type="text" name="event_name" placeholder="Ex: Vokatra 06/2026" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btn-close-modal" style="background: #e74c3c;">Hiverina</button>
                    <button type="submit" style="background: #2ecc71;">Ekena</button>
                </div>
            </form>
        </div>
    </div>

    <div class="main-layout">

        <div class="container">
            <h2>Fandraisana vokatra</h2>
            <form action="/print" method="POST" id="print-form" target="print_popup">

                <div style="background: #ecf0f1; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                    <label for="current_event_select" style="margin-bottom: 0;">Fotoana :</label>

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

                    <button type="button" id="btn-open-modal" style="background: #2ecc71;">+ Hamorona</button>
                </div>

                <div class="search-container">
                    <label for="user-search">Hikaroka mpivavaka :</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="user-search" name="user_name" autocomplete="off" placeholder="Soraty eto ny anarana..." style="flex: 1;">
                        <button type="button" id="btn-quick-create" style="background: #e67e22; display: none;">+ Ampidirina</button>
                    </div>
                    <input type="hidden" id="selected-user-id" name="user_id">
                    <div id="suggestions-list" class="suggestions-dropdown"></div>
                </div>

                <div id="selection-display" class="selection-panel">
                    <span id="confirmed-name" class="selection-text"></span>
                    <span class="selection-status">✓</span>
                </div>

                <div class="form-group">
                    <label>Vokatra :</label>
                    <div id="products_container">
                        <div class="product-row">
                            <input type="text" name="products[0][name]" placeholder="Anaran'ny vokatra" required>
                            <input type="number" name="products[0][qty]" class="qty-input" placeholder="Isany" min="1" required>
                            <input type="number" step="0.01" name="products[0][price]" class="price-input" placeholder="Vidiny tsirairay" required>
                            <button type="button" class="btn-remove-row" style="background: #dc3545;">X</button>
                        </div>
                    </div>
                    <button type="button" id="btn-add-row" class="btn-add" style="margin-top: 10px;">+ Vokatra hafa</button>
                </div>

                <div class="action-buttons">
                    <button type="submit" style="flex: 3; font-size: 15px; background: #007BFF;">Hamoahana rosia</button>
                    <button type="reset" style="flex: 1; font-size: 15px; background: #95a5a6;">Manaraka</button>
                </div>
            </form>
        </div>

        <div class="sidebars-column">
            <div class="sidebar">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #f4f4f9; padding-bottom: 10px;">
                    <h4 style="margin: 0; color: #2c3e50;">Ireo voaray farany</h4>
                    <a href="/vokatra" style="font-size: 13px; color: #3498db; text-decoration: none; font-weight: bold;">Vokatra rehetra →</a>
                </div>

                <ul id="latest-scans-list" style="list-style: none; padding: 0; margin: 0;">
                    <?php if (count($latestScans) === 0): ?>
                        <li style="color: #888; font-style: italic; font-size: 13px; text-align: center; padding: 15px 0;">Tsy mbola misy voaray</li>
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

            <div class="sidebar mpivavaka-sidebar">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #f4f4f9; padding-bottom: 10px;">
                    <h4 style="margin: 0; color: #2c3e50;">Ireo mpivavaka vaovao</h4>
                </div>

                <ul id="new-subscribers-list" style="list-style: none; padding: 0; margin: 0;">
                    <?php if (count($newSubscriber) === 0): ?>
                        <li style="color: #888; font-style: italic; font-size: 13px; text-align: center; padding: 15px 0;">Tsy mbola misy vaovao</li>
                    <?php else: ?>
                        <?php foreach ($newSubscriber as $user): ?>
                            <li style="padding: 10px 0; border-bottom: 1px solid #eee; font-size: 13px;">
                                <div style="display: flex; justify-content: space-between; font-weight: bold;">
                                    <span style="font-family: monospace; color: #3498db;"><?= htmlspecialchars($user['id'] ?? '') ?></span>
                                </div>
                                <div style="color: #7f8c8d; font-size: 12px; margin-top: 2px; text-transform: uppercase;">
                                    <?= htmlspecialchars($user['name'] ?? '') ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

    </div>

    <!-- Notification Container -->
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;"></div>

    <!-- 🚀 EXTERIOR JAVASCRIPT PIPELINE: Loading segregated frontend scripts -->
    <script src="../assets/js/harvest.js"></script>
</body>

</html>