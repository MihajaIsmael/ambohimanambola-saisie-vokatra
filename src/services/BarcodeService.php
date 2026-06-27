<?php

namespace App\Services;

include_once __DIR__ . '/../utils.php';

/**
 * Class BarcodeService
 * Manages the generation and structuring of semi-significant EAN-13 barcodes
 * and 7-digit product references for TakePOS/Dolibarr integration.
 * 
 */
class BarcodeService
{
    /**
     * Generate labels in a vast and dynamic way
     * 
     * @param array $product
     * @param string $userId
     * @param int &$productCounter
     * 
     * @return array
     */
    public static function generateProductLabels(array $product, string $userId, int &$productCounter, array $settings): array
    {
        $labels = [];
        $qty = (int) array_get_default($product, 'qty', 1);
        $price = (float) array_get_default($product, 'price', 0);
        $name = array_get_default($product, 'name', 'Unknown');

        // ID formatted on 4 characters (ex: 564 -> 0564)
        $userFormatted = str_pad($userId, 4, '0', STR_PAD_LEFT);

        // Loop on the quantity requested for this product
        for ($i = 0; $i < $qty; $i++) {

            // AUTOMATISATION : The product number increments continuously (01, 02, 03...)
            $prodFormatted = str_pad((string)$productCounter, 2, '0', STR_PAD_LEFT);

            // Central bloc of 7 digits unique0201 per label
            $productCode7 = $settings['event_id'] . $userFormatted . $prodFormatted;

            // EAN-13 payload construction (12 digits)
            $basePayload = $settings['country_code'] . $settings['year_code'] . $productCode7;

            // 13th digit calculation (check digit)
            $checksum = self::calculateEanChecksum($basePayload);
            $fullBarcode13 = $basePayload . $checksum;

            $labels[] = [
                'barcode'      => $fullBarcode13,
                'product_code' => $productCode7, // Unique for each physical label
                'name'         => $name,
                'price'        => $price
            ];

            // Increment the automatic counter for the next label
            $productCounter++;
        }

        return $labels;
    }

    /**
     * Calculate the EAN-13 checksum
     * 
     * @param string $digits
     * 
     * @return int
     */
    private static function calculateEanChecksum(string $digits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }
}
