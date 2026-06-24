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

    <div class="container">
        <h2>Nouveau Ticket POS80</h2>
        <form action="src/print.php" method="POST">

            <div class="search-container">
                <label for="user-search">Rechercher un Mpivavaka :</label>
                <input type="text" id="user-search" class="search-input" autocomplete="off" placeholder="Tapez les premières lettres...">
                <input type="hidden" id="selected-user-id" name="user_id">
                <div id="suggestions-list" class="suggestions-dropdown"></div>
            </div>

            <div id="selection-display" class="selection-panel">
                <span class="selection-status">✓ Sélectionné :</span>
                <span id="confirmed-name" class="selection-text"></span>
            </div>

            <div class="form-group">
                <label>Produits :</label>
                <div id="products_container">
                    <div class="product-row">
                        <input type="text" name="products[0][name]" placeholder="Nom du produit" required>
                        <input type="number" name="products[0][qty]" class="qty-input" placeholder="Qté" min="1" required>
                        <input type="number" step="0.01" name="products[0][price]" class="price-input" placeholder="Prix" required>
                        <button type="button" class="btn-remove" onclick="removeRow(this)">X</button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addRow()">+ Ajouter un produit</button>
            </div>

            <button type="submit" class="submit-btn">Générer et Imprimer</button>
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
    </script>
</body>

</html>