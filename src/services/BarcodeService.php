<?php

namespace App\Services;

/**
 * Class BarcodeService
 * Manages the generation and structuring of semi-significant EAN-13 barcodes
 * and 7-digit product references for TakePOS/Dolibarr integration.
 * 
 */
class BarcodeService
{
    // Permanent internal prefix
    // TODO For future version, user can make this dynamic
    private const INTERNAL_PREFIX = "26";

    /**
     * Calculates the 13th EAN-13 check digit and returns the full 13-digit barcode string.
     * 
     * @param string $number12Digits Base payload of exactly 12 digits
     * 
     * @return string Full 13-digit EAN-13 barcode
     * 
     * @throws \InvalidArgumentException If input is not exactly 12 digits or contains non-numeric characters
     */
    public static function computeEan13(string $number12Digits): string
    {
        if (!preg_match('/^[0-9]{12}$/', $number12Digits)) {
            throw new \InvalidArgumentException("EAN-13 base payload must be exactly 12 numeric digits.");
        }

        $digits = str_split($number12Digits);
        $sum = 0;

        foreach ($digits as $key => $digit) {
            // Even indexes (0, 2, 4...) represent odd positions in EAN-13 standard (weight 1)
            // Odd indexes (1, 3, 5...) represent even positions in EAN-13 standard (weight 3)
            $sum += ($key % 2 === 0) ? $digit * 1 : $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $number12Digits . $checkDigit;
    }

    /**
     * Extracts a standard 7-digit reference code compatible with Dolibarr.
     * Structure: Prefix (2) + Event ID (2) + Member ID subset or Item Short Code (3)
     * 
     * @param string|int $eventId Unique identifier for the event
     * @param string|int $memberId Unique identifier for the member/mpivavaka
     * 
     * @return string 7-digit product reference code
     */
    public static function generateProductCode7Digits($eventId, $memberId): string
    {
        $eventPart = str_pad(preg_replace('/[^0-9]/', '', $eventId), 2, "0", STR_PAD_LEFT);
        $memberPart = str_pad(preg_replace('/[^0-9]/', '', $memberId), 3, "0", STR_PAD_LEFT);

        // Extract the last 3 digits of the member part if it exceeds length
        $memberPart = substr($memberPart, -3);

        return self::INTERNAL_PREFIX . $eventPart . $memberPart;
    }

    /**
     * Generates an array of individual product labels with structural EAN-13 barcodes.
     * Structure (12 digits base): Prefix(2) + Event(2) + Member(4) + ProductSequence(4)
     * 
     * @param array $product Raw product details containing 'name', 'qty', 'price'
     * @param string|int $eventId Current event identifier
     * @param string|int $memberId Current member/mpivavaka identifier
     * @param int &$itemCounter Global reference counter to ensure uniqueness per item
     * 
     * @return array List of generated labels containing formatting data
     */
    public static function generateProductLabels(array $product, $eventId, $memberId, int &$itemCounter): array
    {
        $qty = (int)$product['qty'];
        $price = (float)$product['price'];
        $prodName = $product['name'];
        $labels = [];

        // Pre-format fixed metadata segments
        $eventPart = str_pad(preg_replace('/[^0-9]/', '', $eventId), 2, "0", STR_PAD_LEFT);
        $memberPart = str_pad(preg_replace('/[^0-9]/', '', $memberId), 4, "0", STR_PAD_LEFT);

        // Generate a unified 7-digit product reference code for Dolibarr mapping
        $productCode7 = self::generateProductCode7Digits($eventId, $memberId);

        for ($i = 1; $i <= $qty; $i++) {
            // Unique sequence per item incremented globally
            $productSequence = str_pad($itemCounter, 4, "0", STR_PAD_LEFT);

            // Assemble the final 12 digits payload
            $baseCode12 = self::INTERNAL_PREFIX . $eventPart . $memberPart . $productSequence;

            // Generate full EAN-13 string safely
            $ean13Code = self::computeEan13($baseCode12);

            $labels[] = [
                'name'         => $prodName,
                'price'        => $price,
                'product_code' => $productCode7,
                'barcode'      => $ean13Code
            ];

            $itemCounter++;
        }

        return $labels;
    }
}
