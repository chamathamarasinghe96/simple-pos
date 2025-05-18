<?php
// modules/sales/process_sale.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db_helpers.php';
require_once __DIR__ . '/../../includes/functions.php';

$products_file = __DIR__ . '/../../data/products.json';
$sales_file = __DIR__ . '/../../data/sales.json';
$settings_file = __DIR__ . '/../../data/settings.json'; // For currency symbol, etc.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from POST
    $cart_data_json = get_post_var('cart_data');
    $client_subtotal = filter_var(get_post_var('subtotal'), FILTER_VALIDATE_FLOAT);
    $client_tax_amount = filter_var(get_post_var('tax_amount'), FILTER_VALIDATE_FLOAT);
    $client_grand_total = filter_var(get_post_var('grand_total'), FILTER_VALIDATE_FLOAT);
    $tax_rate_percent_from_form = filter_var(get_post_var('tax_rate_percent'), FILTER_VALIDATE_FLOAT);
    $payment_method = sanitize_input(get_post_var('payment_method', 'Cash'));

    $cart_items = json_decode($cart_data_json, true);

    // --- Basic Validation ---
    if (json_last_error() !== JSON_ERROR_NONE || empty($cart_items)) {
        set_flash_message('sale_error', 'Invalid cart data or cart is empty. Sale not processed.');
        redirect('new_sale.php');
    }
    if ($client_subtotal === false || $client_tax_amount === false || $client_grand_total === false) {
        set_flash_message('sale_error', 'Invalid sales totals received. Sale not processed.');
        redirect('new_sale.php');
    }

    $all_products = read_json_file($products_file);
    $pos_settings = read_json_file($settings_file);
    $server_tax_rate_percent = (float)($pos_settings['tax_rate_percent'] ?? 0);

    // --- Server-Side Calculation & Stock Validation ---
    $server_calculated_subtotal = 0;
    $validated_cart_items = [];
    $stock_issues = [];

    foreach ($cart_items as $cart_item) {
        $product_id = sanitize_input($cart_item['id']);
        $quantity_to_sell = (int)($cart_item['quantity'] ?? 0);

        if ($quantity_to_sell <= 0) {
            $stock_issues[] = "Invalid quantity for product " . sanitize_input($cart_item['name'] ?? $product_id) . ".";
            continue;
        }

        $product_found_in_system = false;
        foreach ($all_products as $key => $system_product) {
            if (isset($system_product['id']) && $system_product['id'] === $product_id) {
                $product_found_in_system = true;
                if (!isset($system_product['stock']) || $system_product['stock'] < $quantity_to_sell) {
                    $stock_issues[] = "Not enough stock for " . sanitize_input($system_product['name']) . ". Available: " . ($system_product['stock'] ?? 0) . ", Requested: " . $quantity_to_sell . ".";
                } else {
                    $item_price = (float)($system_product['price'] ?? 0);
                    $server_calculated_subtotal += $item_price * $quantity_to_sell;
                    $validated_cart_items[] = [
                        'product_id' => $product_id,
                        'name' => sanitize_input($system_product['name']), // Use name from system
                        'quantity' => $quantity_to_sell,
                        'unit_price' => $item_price, // Use price from system
                        'total_price' => $item_price * $quantity_to_sell,
                        'unit' => sanitize_input($system_product['unit'] ?? '')
                    ];
                }
                break;
            }
        }
        if (!$product_found_in_system) {
            $stock_issues[] = "Product " . sanitize_input($cart_item['name'] ?? $product_id) . " not found in system.";
        }
    }

    if (!empty($stock_issues)) {
        set_flash_message('sale_error', "Sale not processed due to stock/product issues:<br>" . implode("<br>", $stock_issues));
        // Potentially pass cart back to new_sale.php via session to allow user to correct it
        // For simplicity now, just an error.
        $_SESSION['stale_cart_data'] = $cart_data_json; // Send it back for repopulation attempt
        redirect('new_sale.php');
    }
    
    if (empty($validated_cart_items)) { // Double check after stock validation
        set_flash_message('sale_error', 'Cart is effectively empty after validation. Sale not processed.');
        redirect('new_sale.php');
    }


    // Server-side total calculation
    $server_calculated_tax_amount = $server_calculated_subtotal * ($server_tax_rate_percent / 100);
    $server_calculated_grand_total = $server_calculated_subtotal + $server_calculated_tax_amount;

    // Optional: Compare server totals with client totals (allow for small floating point discrepancies)
    $tolerance = 0.011; // Small tolerance for floating point math
    if (abs($server_calculated_grand_total - $client_grand_total) > $tolerance) {
         // Log this discrepancy, but for "minimal" may proceed with server values or reject.
         // For now, we will use server-calculated values.
        // set_flash_message('sale_warning', 'Client-server total mismatch. Using server calculated totals. Client: '. $client_grand_total . ' Server: ' . $server_calculated_grand_total);
    }


    // --- Update Inventory ---
    $inventory_updated_successfully = true;
    $products_after_sale = $all_products; // Create a working copy

    foreach ($validated_cart_items as $sold_item) {
        foreach ($products_after_sale as $key => &$system_product_ref) { // Use reference
            if (isset($system_product_ref['id']) && $system_product_ref['id'] === $sold_item['product_id']) {
                $system_product_ref['stock'] = ($system_product_ref['stock'] ?? 0) - $sold_item['quantity'];
                $system_product_ref['last_updated'] = date('Y-m-d H:i:s');
                break;
            }
        }
        unset($system_product_ref); // Important to unset reference
    }

    if (!write_json_file($products_file, $products_after_sale)) {
        $inventory_updated_successfully = false;
        set_flash_message('sale_error', 'Critical error: Sale processed but failed to update inventory. Please check manually.');
        // This is a serious issue; might need manual reconciliation.
        // For now, we'll still try to record the sale but with a strong warning.
        // A more robust system might try to roll back or queue for retry.
    }


    // --- Record Sale Transaction ---
    $transaction_id = generate_transaction_id($sales_file); // From db_helpers.php
    $current_datetime = new DateTime('now', new DateTimeZone('Asia/Colombo')); // Using Sri Lankan timezone


    $new_sale_record = [
        'transaction_id' => $transaction_id,
        'date' => $current_datetime->format('Y-m-d'),
        'time' => $current_datetime->format('H:i:s'),
        'items' => $validated_cart_items, // Use validated items with server prices
        'subtotal' => (float)number_format($server_calculated_subtotal, 2, '.', ''),
        'tax_rate_percent' => (float)$server_tax_rate_percent,
        'tax_amount' => (float)number_format($server_calculated_tax_amount, 2, '.', ''),
        'grand_total' => (float)number_format($server_calculated_grand_total, 2, '.', ''),
        'payment_method' => $payment_method,
        'timezone' => 'Asia/Colombo'
    ];

    $all_sales = read_json_file($sales_file);
    $all_sales[] = $new_sale_record;

    if (write_json_file($sales_file, $all_sales)) {
        if ($inventory_updated_successfully) {
            set_flash_message('sale_success', 'Sale completed successfully! Transaction ID: ' . $transaction_id);
        } else {
            // Message already set about inventory failure, but confirm sale was recorded
            set_flash_message('sale_warning', 'Sale recorded (ID: ' . $transaction_id . '), but inventory update failed. Please check stock levels manually.');
        }
        // Clear any stale cart data from session
        unset($_SESSION['stale_cart_data']);

        // Option: Redirect to a receipt page: redirect('receipt.php?transaction_id=' . $transaction_id);
        redirect('new_sale.php'); // Redirect to a new sale page
    } else {
        set_flash_message('sale_error', 'Failed to record sale transaction after inventory update. Please check logs and reconcile manually.');
        // This is also critical. Inventory might be reduced, but sale not logged.
        // A robust system would attempt to roll back inventory changes or flag for reconciliation.
        redirect('new_sale.php');
    }

} else {
    // Not a POST request
    set_flash_message('sale_error', 'Invalid access method.');
    redirect('new_sale.php');
}
?>