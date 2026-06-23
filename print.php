<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$userId = $_POST['user_id'] ?? 'Inconnu';
$userName = $_POST['user_name'] ?? 'Inconnu';
$products = $_POST['products'] ?? [];

// Génération d'un code de transaction unique (ex: Timestamp + ID Utilisateur)
$transactionCode = time() . $userId; 

$total = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Impression Ticket</title>
    <style>
        /* Configuration de la page pour le POS80 */
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            width: 72mm; /* Marge de sécurité pour un rouleau de 80mm */
            margin: 0 auto;
            padding: 5mm 0;
            font-family: 'Courier New', Courier, monospace; /* Police ticket classique */
            font-size: 12px;
            color: #000;
        }
        .ticket-section {
            width: 100%;
            /* Force un saut de page après cette section pour la découpe */
            page-break-after: always; 
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        .ticket-section:last-child {
            page-break-after: avoid; /* Pas de saut de page sur le dernier morceau */
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 8px 0; }
        
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        
        .barcode {
            margin: 15px 0;
            text-align: center;
        }
        .barcode img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>

    <div class="ticket-section">
        <p class="text-center bold" style="font-size: 14px;">TICKET DESCRIPTION</p>
        <div class="line"></div>
        <p><strong>Client ID :</strong> <?php echo htmlspecialchars($userId); ?></p>
        <p><strong>Nom :</strong> <?php echo htmlspecialchars($userName); ?></p>
        <div class="line"></div>
        
        <table>
            <thead>
                <tr class="bold">
                    <td>Art.</td>
                    <td class="text-center">Qté</td>
                    <td class="text-right">Prix</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $prod): 
                    $subtotal = $prod['qty'] * $prod['price'];
                    $total += $subtotal;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td class="text-center"><?php echo (int)$prod['qty']; ?></td>
                        <td class="text-right"><?php echo number_format($subtotal, 2, ',', ' '); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="line"></div>
        <p class="text-center">-- Fin de description --</p>
    </div>

    <div class="ticket-section">
        <p class="text-center bold" style="font-size: 14px;">TICKET ENCAISSEMENT</p>
        <div class="line"></div>
        
        <h2 class="text-center" style="margin: 10px 0; font-size: 18px;">
            TOTAL : <?php echo number_format($total, 2, ',', ' '); ?> Ar
        </h2>
        
        <div class="line"></div>
        
        <div class="barcode">
            <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=<?php echo $transactionCode; ?>&scale=2&rotate=N&includetext" alt="Code-barres">
            <br>
            <small><?php echo $transactionCode; ?></small>
        </div>
        
        <div class="line"></div>
        <p class="text-center" style="font-size: 10px;">Merci de votre visite</p>
    </div>

<script>
    // Lance automatiquement la boîte de dialogue d'impression au chargement
    window.onload = function() {
        window.print();
        // Optionnel : redirection vers l'index après impression
        // window.location.href = 'index.php';
    }
</script>
</body>
</html>