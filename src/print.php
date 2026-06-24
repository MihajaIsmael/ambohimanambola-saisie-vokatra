<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$userId = $_POST['user_id'] ?? 'Inconnu';
$userName = $_POST['user_name'] ?? 'Inconnu';
$products = $_POST['products'] ?? [];

// Date de la récolte
$dateRecolte = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Impression Récolte & Étiquettes</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        body {
            width: 72mm;
            margin: 0 auto;
            padding: 5mm 0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
        }

        /* Style pour forcer le massicot de l'imprimante à chaque section */
        .ticket-section {
            width: 100%;
            page-break-after: always;
            box-sizing: border-box;
            padding-bottom: 10px;
        }

        /* La toute dernière étiquette ne doit pas forcer de page blanche vide */
        .ticket-section:last-child {
            page-break-after: avoid;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-bottom: 1px dashed #000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 4px 0;
        }

        .barcode {
            margin: 10px 0;
            text-align: center;
        }

        .barcode img {
            max-width: 90%;
            height: auto;
        }

        .price-tag {
            font-size: 18px;
            font-weight: bold;
            margin: 8px 0;
        }
    </style>
</head>

<body>

    <div class="ticket-section">
        <p class="text-center bold" style="font-size: 14px;">REÇU DE DÉPÔT / RÉCOLTE</p>
        <p class="text-center" style="font-size: 10px;">Date : <?php echo $dateRecolte; ?></p>
        <div class="line"></div>

        <p><strong>Producteur :</strong> <?php echo htmlspecialchars($userName); ?> (ID: <?php echo htmlspecialchars($userId); ?>)</p>
        <div class="line"></div>

        <table>
            <thead>
                <tr class="bold">
                    <td>Désignation</td>
                    <td class="text-right">Quantité</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $prod): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td class="text-right bold">x <?php echo (int)$prod['qty']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="line"></div>
        <p class="text-center" style="font-size: 10px;">Document justificatif producteur</p>
    </div>


    <?php
    $itemCounter = 1;
    foreach ($products as $prod):
        $qty = (int)$prod['qty'];
        $price = (float)$prod['price'];
        $prodName = $prod['name'];

        // On va générer autant d'étiquettes que de quantité spécifiée
        for ($i = 1; $i <= $qty; $i++):
            // Création d'un code unique basé sur le timestamp, l'index et la position
            // Idéalement à lier plus tard avec ton futur fichier d'import Dolibarr
            $uniqueBarcodeText = "REC-" . time() . "-" . $itemCounter;
    ?>
            <div class="ticket-section">
                <p class="text-center bold" style="font-size: 11px; margin: 0;">ÉTIQUETTE PRODUIT</p>
                <div class="line"></div>

                <p class="text-center bold" style="font-size: 15px; margin: 5px 0;">
                    <?php echo htmlspecialchars($prodName); ?>
                </p>

                <p class="text-center price-tag">
                    <?php echo number_format($price, 0, '.', ' '); ?> Ar
                </p>

                <div class="barcode">
                    <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=<?php echo $uniqueBarcodeText; ?>&scale=2&rotate=N&includetext" alt="Code-barres">
                </div>

                <p class="text-center" style="font-size: 9px; color: #555;">Origine: ID-<?php echo htmlspecialchars($userId); ?></p>
            </div>
    <?php
            $itemCounter++;
        endfor;
    endforeach;
    ?>

    <script>
        // Déclenchement automatique de l'impression
        window.onload = function() {
            window.print();
            // Optionnel : décommente la ligne ci-dessous si tu veux rediriger l'utilisateur vers la saisie juste après
            // window.location.href = '../index.php';
        }
    </script>
</body>

</html>