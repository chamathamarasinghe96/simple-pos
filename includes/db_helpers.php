<?php
// includes/db_helpers.php

/**
 * Reads data from a JSON file.
 *
 * @param string $filePath The path to the JSON file.
 * @return array An array of data from the JSON file, or an empty array if the file doesn't exist or is invalid.
 */
function read_json_file(string $filePath): array {
    if (!file_exists($filePath)) {
        // If the file doesn't exist, it's often better to return an empty array
        // than to cause an error, especially for things like an empty list of products.
        return [];
    }

    $jsonContent = file_get_contents($filePath);
    if ($jsonContent === false) {
        // Handle error reading file
        error_log("Error reading file: " . $filePath);
        return [];
    }

    $data = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // Handle JSON decoding error
        error_log("JSON decode error in file: " . $filePath . " - " . json_last_error_msg());
        return []; // Or throw an exception, depending on desired error handling
    }

    return is_array($data) ? $data : [];
}

/**
 * Writes data to a JSON file.
 * Uses LOCK_EX to prevent race conditions during writing.
 *
 * @param string $filePath The path to the JSON file.
 * @param array $data The data to write to the file.
 * @return bool True on success, false on failure.
 */
function write_json_file(string $filePath, array $data): bool {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON encode error: " . json_last_error_msg());
        return false;
    }

    // Ensure the directory exists
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            error_log("Failed to create directory: " . $directory);
            return false;
        }
    }

    if (file_put_contents($filePath, $jsonData, LOCK_EX) === false) {
        error_log("Error writing to file: " . $filePath);
        return false;
    }

    return true;
}

/**
 * Generates a simple unique ID.
 * For a more robust system, consider UUIDs.
 * This is a very basic sequential ID based on existing items or a timestamp.
 *
 * @param array $existingItems Array of existing items to check the last ID.
 * @param string $prefix Prefix for the ID (e.g., 'P' for Product, 'S' for Sale).
 * @return string A new unique ID.
 */
function generate_unique_id(array $existingItems, string $prefix = ''): string {
    if (empty($existingItems)) {
        return $prefix . '1';
    }
    $lastItem = end($existingItems);
    if (isset($lastItem['id'])) {
        $lastIdNum = (int) str_replace($prefix, '', $lastItem['id']);
        return $prefix . ($lastIdNum + 1);
    }
    // Fallback if 'id' is not found or format is unexpected
    return $prefix . (count($existingItems) + 1) . '_' . time();
}

/**
 * Generates a unique transaction ID for sales.
 * Format: S<YYYYMMDD><3-digit-sequential-number-for-the-day>
 * e.g., S20250518001
 *
 * @param string $salesFilePath Path to the sales.json file.
 * @return string A new unique transaction ID.
 */
function generate_transaction_id(string $salesFilePath): string {
    $sales = read_json_file($salesFilePath);
    $today = date("Ymd");
    $prefix = "S" . $today;
    $counter = 1;

    $salesToday = array_filter($sales, function ($sale) use ($prefix) {
        return isset($sale['transaction_id']) && strpos($sale['transaction_id'], $prefix) === 0;
    });

    if (!empty($salesToday)) {
        $lastSaleToday = end($salesToday);
        if (isset($lastSaleToday['transaction_id'])) {
            $lastCounter = (int) substr($lastSaleToday['transaction_id'], strlen($prefix));
            $counter = $lastCounter + 1;
        }
    }
    return $prefix . str_pad($counter, 3, '0', STR_PAD_LEFT);
}

?>