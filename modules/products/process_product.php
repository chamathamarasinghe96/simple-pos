<?php
// modules/products/process_product.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db_helpers.php';
require_once __DIR__ . '/../../includes/functions.php';

$products_file = __DIR__ . '/../../data/products.json';
$categories_file = __DIR__ . '/../../data/categories.json'; // Needed to fetch category name

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = get_post_var('action');

    // --- ACTION: ADD PRODUCT ---
    if ($action === 'add_product') {
        $product_name = sanitize_input(get_post_var('product_name'));
        $category_id = sanitize_input(get_post_var('category_id'));
        $unit_price = filter_var(get_post_var('unit_price'), FILTER_VALIDATE_FLOAT);
        $unit_of_measure = sanitize_input(get_post_var('unit_of_measure'));
        $current_stock = filter_var(get_post_var('current_stock'), FILTER_VALIDATE_INT);
        $low_stock_threshold_val = get_post_var('low_stock_threshold');
        $low_stock_threshold = ($low_stock_threshold_val === '' || $low_stock_threshold_val === null) ? null : filter_var($low_stock_threshold_val, FILTER_VALIDATE_INT);

        // Basic Validation
        $errors = [];
        if (empty($product_name)) {
            $errors[] = "Product name is required.";
        }
        if (empty($category_id)) {
            $errors[] = "Category is required.";
        }
        if ($unit_price === false || $unit_price < 0) {
            $errors[] = "Valid unit price is required.";
        }
        if (empty($unit_of_measure)) {
            $errors[] = "Unit of measure is required.";
        }
        if ($current_stock === false || $current_stock < 0) {
            $errors[] = "Valid current stock quantity is required.";
        }
        if ($low_stock_threshold_val !== '' && $low_stock_threshold_val !== null && ($low_stock_threshold === false || $low_stock_threshold < 0)) {
            $errors[] = "Valid low stock threshold is required if provided.";
        }

        if (!empty($errors)) {
            // Store errors and form data in session to display on the form page
            $_SESSION['product_add_errors'] = $errors;
            $_SESSION['sticky_form_data'] = $_POST; // Store all POST data for stickiness
            set_flash_message('product_add_error', implode("<br>", $errors));
            redirect('add_product.php');
        }

        // Fetch category name
        $categories = read_json_file($categories_file);
        $category_name = 'Uncategorized'; // Default
        foreach ($categories as $cat) {
            if (isset($cat['id']) && $cat['id'] === $category_id) {
                $category_name = sanitize_input($cat['name']);
                break;
            }
        }

        $products = read_json_file($products_file);
        $new_product_id = generate_unique_id($products, 'P');

        $new_product = [
            'id' => $new_product_id,
            'name' => $product_name,
            'category_id' => $category_id, // Store category ID
            'category_name' => $category_name, // Store category name for easier display
            'price' => (float)$unit_price,
            'unit' => $unit_of_measure,
            'stock' => (int)$current_stock,
            'low_stock_threshold' => ($low_stock_threshold === null) ? 0 : (int)$low_stock_threshold, // Store 0 if not set or a valid int
            'date_added' => date('Y-m-d H:i:s'),
            'last_updated' => date('Y-m-d H:i:s')
        ];

        $products[] = $new_product;

        if (write_json_file($products_file, $products)) {
            set_flash_message('product_action_success', 'Product "' . $product_name . '" added successfully!');
            unset($_SESSION['sticky_form_data']); // Clear sticky data on success
            redirect('list_products.php');
        } else {
            set_flash_message('product_add_error', 'Failed to add product. Please check file permissions or logs.');
            $_SESSION['sticky_form_data'] = $_POST;
            redirect('add_product.php');
        }
    }
    // --- ACTION: EDIT PRODUCT ---
    elseif ($action === 'edit_product') {
        $product_id = sanitize_input(get_post_var('product_id'));
        $product_name = sanitize_input(get_post_var('product_name'));
        $category_id = sanitize_input(get_post_var('category_id'));
        $unit_price = filter_var(get_post_var('unit_price'), FILTER_VALIDATE_FLOAT);
        $unit_of_measure = sanitize_input(get_post_var('unit_of_measure'));
        $current_stock = filter_var(get_post_var('current_stock'), FILTER_VALIDATE_INT);
        $low_stock_threshold_val = get_post_var('low_stock_threshold');
        $low_stock_threshold = ($low_stock_threshold_val === '' || $low_stock_threshold_val === null) ? null : filter_var($low_stock_threshold_val, FILTER_VALIDATE_INT);

        // Basic Validation
        $errors = [];
        if (empty($product_id)) {
            $errors[] = "Product ID is missing.";
        }
        if (empty($product_name)) {
            $errors[] = "Product name is required.";
        }
        if (empty($category_id)) {
            $errors[] = "Category is required.";
        }
        if ($unit_price === false || $unit_price < 0) {
            $errors[] = "Valid unit price is required.";
        }
        if (empty($unit_of_measure)) {
            $errors[] = "Unit of measure is required.";
        }
        if ($current_stock === false || $current_stock < 0) {
            $errors[] = "Valid current stock quantity is required.";
        }
        if ($low_stock_threshold_val !== '' && $low_stock_threshold_val !== null && ($low_stock_threshold === false || $low_stock_threshold < 0)) {
             $errors[] = "Valid low stock threshold is required if provided.";
        }

        if (!empty($errors)) {
            $_SESSION['product_edit_errors_' . $product_id] = $errors; // Specific error key for edit page
            $_SESSION['sticky_form_data_' . $product_id] = $_POST;
            set_flash_message('product_edit_error', implode("<br>", $errors));
            redirect('edit_product.php?id=' . $product_id);
        }

        $products = read_json_file($products_file);
        $product_found = false;
        $updated_products = [];

        // Fetch category name
        $categories = read_json_file($categories_file);
        $category_name = 'Uncategorized'; // Default
        foreach ($categories as $cat) {
            if (isset($cat['id']) && $cat['id'] === $category_id) {
                $category_name = sanitize_input($cat['name']);
                break;
            }
        }

        foreach ($products as $key => $product) {
            if (isset($product['id']) && $product['id'] === $product_id) {
                $products[$key]['name'] = $product_name;
                $products[$key]['category_id'] = $category_id;
                $products[$key]['category_name'] = $category_name;
                $products[$key]['price'] = (float)$unit_price;
                $products[$key]['unit'] = $unit_of_measure;
                $products[$key]['stock'] = (int)$current_stock;
                $products[$key]['low_stock_threshold'] = ($low_stock_threshold === null) ? $product['low_stock_threshold'] : (int)$low_stock_threshold; // Retain old if not provided or set to valid int
                $products[$key]['last_updated'] = date('Y-m-d H:i:s');
                $product_found = true;
                break; 
            }
        }

        if ($product_found) {
            if (write_json_file($products_file, $products)) {
                set_flash_message('product_action_success', 'Product "' . $product_name . '" updated successfully!');
                unset($_SESSION['sticky_form_data_' . $product_id]);
                redirect('list_products.php');
            } else {
                set_flash_message('product_edit_error', 'Failed to update product. Please check file permissions or logs.');
                $_SESSION['sticky_form_data_' . $product_id] = $_POST;
                redirect('edit_product.php?id=' . $product_id);
            }
        } else {
            set_flash_message('product_edit_error', 'Product not found for editing.');
            redirect('list_products.php');
        }
    }
    // --- ACTION: DELETE PRODUCT ---
    elseif ($action === 'delete_product') {
        $product_id = sanitize_input(get_post_var('product_id'));

        if (empty($product_id)) {
            set_flash_message('product_action_error', 'Product ID is missing for deletion.');
            redirect('list_products.php');
        }

        $products = read_json_file($products_file);
        $product_to_delete_name = '';
        $initial_count = count($products);

        $updated_products = array_filter($products, function ($product) use ($product_id, &$product_to_delete_name) {
            if (isset($product['id']) && $product['id'] === $product_id) {
                $product_to_delete_name = $product['name'];
                return false; // Do not include this product in the new array
            }
            return true; // Keep this product
        });
        
        // Re-index array if you care about sequential numeric keys after filtering
        // $updated_products = array_values($updated_products);

        if (count($updated_products) < $initial_count) { // Check if a product was actually removed
            if (write_json_file($products_file, $updated_products)) {
                set_flash_message('product_action_success', 'Product "' . sanitize_input($product_to_delete_name) . '" deleted successfully!');
            } else {
                set_flash_message('product_action_error', 'Failed to delete product. Please check file permissions or logs.');
            }
        } else {
            set_flash_message('product_action_error', 'Product not found for deletion or already deleted.');
        }
        redirect('list_products.php');
    }
    // --- UNKNOWN ACTION ---
    else {
        set_flash_message('product_action_error', 'Invalid action specified.');
        redirect('list_products.php');
    }
} else {
    // Not a POST request, redirect to a safe page
    redirect(base_url() . 'index.php');
}
?>