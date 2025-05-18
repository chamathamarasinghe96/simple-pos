<?php
// modules/inventory/update_stock.php
$page_title = "Update Stock Manually";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php'; // Also includes functions.php and db_helpers.php

$products_file = __DIR__ . '/../../data/products.json';
$all_products = read_json_file($products_file);

// Sort products by name for the dropdown
usort($all_products, function($a, $b) {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
});

$selected_product_id_sticky = '';
$new_stock_quantity_sticky = '';

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id_to_update = sanitize_input(get_post_var('product_id'));
    $new_stock_quantity_val = get_post_var('new_stock_quantity');
    
    // For sticky form
    $selected_product_id_sticky = $product_id_to_update;
    $new_stock_quantity_sticky = $new_stock_quantity_val;

    $errors = [];
    if (empty($product_id_to_update)) {
        $errors[] = "Please select a product.";
    }
    if ($new_stock_quantity_val === '' || $new_stock_quantity_val === null) {
        $errors[] = "New stock quantity is required.";
    } else {
        $new_stock_quantity = filter_var($new_stock_quantity_val, FILTER_VALIDATE_INT);
        if ($new_stock_quantity === false || $new_stock_quantity < 0) {
            $errors[] = "New stock quantity must be a valid non-negative number.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['stock_update_errors'] = $errors;
        // We are POSTing to self, so sticky data is directly available via $_POST
        // and errors via $_SESSION for this request cycle.
        // No need to redirect here to show errors, they will be shown below.
        set_flash_message('stock_update_error_form', implode("<br>", $errors));

    } else {
        $product_found = false;
        $updated_product_name = '';

        foreach ($all_products as $key => &$product_ref) { // Use reference to update directly
            if (isset($product_ref['id']) && $product_ref['id'] === $product_id_to_update) {
                $product_ref['stock'] = (int)$new_stock_quantity;
                $product_ref['last_updated'] = date('Y-m-d H:i:s');
                $product_found = true;
                $updated_product_name = $product_ref['name'];
                break;
            }
        }
        unset($product_ref); // Unset reference

        if ($product_found) {
            if (write_json_file($products_file, $all_products)) {
                set_flash_message('stock_update_success', 'Stock for "' . sanitize_input($updated_product_name) . '" updated to ' . $new_stock_quantity . '.');
                // Refresh all_products array after update for the form display if needed, or redirect
                $all_products = read_json_file($products_file); // Re-read for updated stock in dropdown
                // Clear sticky values on success
                $selected_product_id_sticky = '';
                $new_stock_quantity_sticky = '';
                 // Redirect to prevent re-submission on refresh and to show message on view_stock
                redirect('view_stock.php');
            } else {
                set_flash_message('stock_update_error', 'Failed to update stock. Please check file permissions or logs.');
            }
        } else {
            set_flash_message('stock_update_error', 'Product not found for stock update.');
        }
    }
}

// Retrieve errors from session if set by the POST handler above for display
$form_errors = $_SESSION['stock_update_errors'] ?? [];
unset($_SESSION['stock_update_errors']); // Clear after retrieving

?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Update Stock Manually</h1>
            <a href="view_stock.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-150">
                Back to Stock View
            </a>
        </div>

        <?php
        // Display general flash messages
        display_flash_message('stock_update_error', 'error'); // For errors not related to form validation itself
        // display_flash_message('stock_update_success', 'success'); // Success is usually shown on redirect target
        
        // Display form validation errors, if any
        if (!empty($form_errors)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Please correct the following errors:</p>
                <ul class="list-disc ml-5 mt-2">
                    <?php foreach ($form_errors as $error): ?>
                        <li><?php echo sanitize_input($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <p class="text-sm text-gray-600 mb-4">
            Select a product and enter the new total stock quantity.
        </p>

        <form action="update_stock.php" method="POST" class="space-y-6">
            <div>
                <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Product <span class="text-red-500">*</span></label>
                <select name="product_id" id="product_id" required x-data="{selectedProduct: '<?php echo $selected_product_id_sticky; ?>'}" x-init="$watch('selectedProduct', value => document.getElementById('current_stock_display').innerText = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex].dataset.currentStock || 'N/A')"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">-- Select a Product --</option>
                    <?php foreach ($all_products as $product): ?>
                        <?php if (isset($product['id']) && isset($product['name'])): ?>
                            <option value="<?php echo sanitize_input($product['id']); ?>" 
                                    data-current-stock="<?php echo sanitize_input($product['stock'] ?? '0'); ?>"
                                <?php echo ($selected_product_id_sticky == $product['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize_input($product['name']); ?> (Current Stock: <?php echo sanitize_input($product['stock'] ?? '0'); ?> <?php echo sanitize_input($product['unit'] ?? '');?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="text-sm text-gray-600">
                Current stock for selected product: <strong id="current_stock_display" class="font-semibold">
                    <?php
                        // Initialize display for current stock if a product was already selected (e.g. sticky form)
                        if (!empty($selected_product_id_sticky)) {
                            foreach($all_products as $p) {
                                if ($p['id'] === $selected_product_id_sticky) {
                                    echo sanitize_input($p['stock'] ?? 'N/A');
                                    break;
                                }
                            }
                        } else {
                            echo 'N/A';
                        }
                    ?>
                </strong>
            </div>

            <div>
                <label for="new_stock_quantity" class="block text-sm font-medium text-gray-700 mb-1">New Total Stock Quantity <span class="text-red-500">*</span></label>
                <input type="number" name="new_stock_quantity" id="new_stock_quantity" step="1" min="0" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo sanitize_input($new_stock_quantity_sticky); ?>"
                       placeholder="Enter the new total stock">
            </div>
            
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    Update Stock
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const stockDisplay = document.getElementById('current_stock_display');

    function updateStockDisplay() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.currentStock) {
            stockDisplay.textContent = selectedOption.dataset.currentStock;
        } else {
            stockDisplay.textContent = 'N/A';
        }
    }

    if (productSelect) {
        productSelect.addEventListener('change', updateStockDisplay);
        // Initial call in case a product is pre-selected by PHP (sticky form)
        // The Alpine.js x-init also tries to do this, this is a fallback or alternative
        // if not using Alpine for this specific part.
        if(productSelect.value !== ''){
             updateStockDisplay();
        }
    }
});
</script>


<?php
require_once __DIR__ . '/../../includes/footer.php';
?>