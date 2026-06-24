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

        .product-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .product-row input {
            flex: 1;
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
    </style>
</head>

<body>

    <div class="container">
        <h2>Nouveau Ticket POS80</h2>
        <form action="src/print.php" method="POST">

            <div class="form-group">
                <label for="search_user">Rechercher le client (Rukovoditel) :</label>
                <input type="text" id="search_user" placeholder="Tapez un nom..." autocomplete="off">
                <input type="hidden" id="user_id" name="user_id" required>
                <input type="hidden" id="user_name" name="user_name" required>
                <small id="user_selected_moniteur" style="color: green; font-weight: bold;"></small>
            </div>

            <div class="form-group">
                <label>Produits :</label>
                <div id="products_container">
                    <div class="product-row">
                        <input type="text" name="products[0][name]" placeholder="Nom du produit" required>
                        <input type="number" name="products[0][qty]" placeholder="Qté" min="1" style="max-width: 70px;" required>
                        <input type="number" step="0.01" name="products[0][price]" placeholder="Prix" style="max-width: 100px;" required>
                        <button type="button" class="btn-remove" onclick="removeRow(this)">X</button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addRow()">+ Ajouter un produit</button>
            </div>

            <button type="submit" style="width: 100%; margin-top: 20px; font-size: 16px;">Générer et Imprimer</button>
        </form>
    </div>

    <script>
        // Simulation d'une recherche AJAX dans Rukovoditel
        const searchInput = document.getElementById('search_user');
        searchInput.addEventListener('input', function() {
            const query = this.value;
            if (query.length > 2) {
                // Ici, on appelle notre passerelle PHP vers Rukovoditel
                fetch('src/api/getUser.php?q=' + encodeURIComponent(query))
                    .brhen(response => response.json())
                    .brhen(data => {
                        if (data.length > 0) {
                            // Pour l'exemple, on prend le premier résultat trouvé
                            document.getElementById('user_id').value = data[0].id;
                            document.getElementById('user_name').value = data[0].name;
                            document.getElementById('user_selected_moniteur').innerText = "Sélectionné : " + data[0].name + " (ID: " + data[0].id + ")";
                        }
                    });
            }
        });

        let rowIdx = 1;

        function addRow() {
            const container = document.getElementById('products_container');
            const div = document.createElement('div');
            div.className = 'product-row';
            div.innerHTML = `
            <input type="text" name="products[${rowIdx}][name]" placeholder="Nom du produit" required>
            <input type="number" name="products[${rowIdx}][qty]" placeholder="Qté" min="1" style="max-width: 70px;" required>
            <input type="number" step="0.01" name="products[${rowIdx}][price]" placeholder="Prix" style="max-width: 100px;" required>
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
    </script>

</body>

</html>