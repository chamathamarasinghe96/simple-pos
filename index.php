<?php
// index.php
$page_title = "Dashboard";
require_once __DIR__ . '/includes/header.php'; // Also includes functions.php and db_helpers.php

// Define paths to data files
$products_file = __DIR__ . '/data/products.json';
$sales_file = __DIR__ . '/data/sales.json';
$settings_file = __DIR__ . '/data/settings.json';

// Fetch data for dashboard widgets
$products = read_json_file($products_file);
$sales = read_json_file($sales_file);
$settings = read_json_file($settings_file);

// --- Dashboard Metrics ---
$total_products = count($products);

$low_stock_items = 0;
foreach ($products as $product) {
    if (isset($product['stock']) && isset($product['low_stock_threshold']) && $product['stock'] < $product['low_stock_threshold']) {
        $low_stock_items++;
    }
}

$today_date_string = date("Y-m-d");
$todays_sales_count = 0;
$todays_sales_total = 0.00;

foreach ($sales as $sale) {
    if (isset($sale['date']) && $sale['date'] === $today_date_string) {
        $todays_sales_count++;
        if (isset($sale['grand_total'])) {
            $todays_sales_total += (float)$sale['grand_total'];
        }
    }
}

$shop_name_display = isset($settings['shop_name']) && !empty($settings['shop_name']) ? sanitize_input($settings['shop_name']) : "Your Grocery Store";

?>

<div class="container mx-auto px-4 py-8">
    <header class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Welcome to <?php echo $shop_name_display; ?> POS</h1>
        <p class="text-gray-600">Your simple solution for managing sales and inventory.</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="bg-blue-500 text-white p-3 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Total Products</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $total_products; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="bg-green-500 text-white p-3 rounded-full mr-4">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Today's Sales</p>
                    <p class="text-xl font-bold text-gray-800"><?php echo $todays_sales_count; ?> transaction(s)</p>
                    <p class="text-lg text-gray-700"><?php echo format_price($todays_sales_total); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="bg-red-500 text-white p-3 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Low Stock Items</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $low_stock_items; ?></p>
                </div>
            </div>
             <?php if ($low_stock_items > 0): ?>
                <a href="<?php echo base_url(); ?>modules/inventory/view_stock.php?filter=low_stock" class="text-sm text-blue-500 hover:underline mt-2 inline-block">View Details</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-10">
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="<?php echo base_url(); ?>modules/sales/new_sale.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-6 rounded-lg shadow-md transition duration-150 ease-in-out text-center">
                <div class="flex flex-col items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    <span>New Sale</span>
                </div>
            </a>
            <a href="<?php echo base_url(); ?>modules/products/add_product.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 px-6 rounded-lg shadow-md transition duration-150 ease-in-out text-center">
                 <div class="flex flex-col items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Add New Product</span>
                </div>
            </a>
            <a href="<?php echo base_url(); ?>modules/inventory/view_stock.php" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-4 px-6 rounded-lg shadow-md transition duration-150 ease-in-out text-center">
                <div class="flex flex-col items-center justify-center">
                   <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4M4 7s0 0 0 0M12 15v4M8 11l4 4 4-4M8 7h8" /></svg>
                    <span>View Inventory</span>
                </div>
            </a>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>