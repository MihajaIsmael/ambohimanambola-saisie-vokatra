<?php
/** @var array $labelsToPrint  fourni par ReprintController */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réimpression étiquettes</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body {
            width: 72mm; margin: 0 auto; padding: 5mm 0;
            font-family: 'Calibri', 'Segoe UI', Arial, sans-serif;
            font-size: 13px; color: #000;
        }
        .ticket-section {
            width: 100%; page-break-after: always; box-sizing: border-box;
            padding-bottom: 10px; margin-top: 1px;
        }
        .ticket-section:last-of-type { page-break-after: avoid; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 8px 0; }
        .barcode {
            margin: 15px 0 5px 0; text-align: center;
            display: flex; justify-content: center; align-items: center;
        }
        .barcode svg { max-width: 100%; height: auto; }
        .code { font-size: 15px; font-weight: bold; text-align: center; margin-bottom: 5px; }
        .price-tag { font-size: 20px; font-weight: bold; text-align: center; margin: 5px 0; }
        .barcode svg text {
            font-family: 'Calibri', 'Arial', sans-serif !important;
            font-size: 16px !important; font-weight: bold !important;
        }
    </style>
</head>
<body>

<?php foreach ($labelsToPrint as $label): ?>
    <?php
        $code  = strtoupper(htmlspecialchars($label['product_code'] ?? ''));
        $name  = strtoupper(htmlspecialchars($label['name'] ?? ''));
        $price = number_format($label['price'] ?? 0, 0, '.', ' ');
    ?>
    <div class="ticket-section">
        <p class="text-center" style="font-size: 11px; margin: 0;">Etikety ho an'ny vokatra</p>
        <div class="line"></div>

        <div class="code"><?= $code ?></div>
        <p class="text-center bold" style="font-size: 20px; margin: 5px 0;"><?= $name ?></p>
        <p class="text-center price-tag"><?= $price ?> MGA</p>

        <div class="line"></div>

        <div class="code"><?= $code ?></div>
        <p class="text-center bold" style="font-size: 20px; margin: 5px 0;"><?= $name ?></p>
        <p class="text-center price-tag"><?= $price ?> MGA</p>

        <div class="barcode">
            <svg class="barcode-generator" data-value="<?= htmlspecialchars($label['barcode'] ?? '') ?>"></svg>
        </div>
    </div>
<?php endforeach; ?>

<script src="/assets/js/JsBarcode.all.min.js"></script>
<script>
    window.onload = function () {
        document.querySelectorAll('.barcode-generator').forEach(function (el) {
            const value = el.getAttribute('data-value');
            try {
                JsBarcode(el, value, {
                    format: "EAN13", width: 2, height: 70, displayValue: true,
                    font: "calibri", fontSize: 15, textMargin: 2, background: "#ffffff"
                });
            } catch (error) {
                console.error("Erreur code-barres : " + value, error);
                el.parentElement.innerHTML = "<span style='color:red;font-weight:bold;'>[Erreur code-barres]</span>";
            }
        });
        setTimeout(function () { window.print(); }, 300);
    };
</script>
</body>
</html>