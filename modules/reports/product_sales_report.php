<?php
// modules/reports/product_sales_report.php
$page_title = "Product Sales Report";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php'; // Also includes functions.php and db_helpers.php

$sales_file = __DIR__ . '/../../data/sales.json';
$all_sales_data = read_json_file($sales_file);
$currency_symbol = $settings['currency_symbol'] ?? '$'; // From header.php

// Determine the report date range
$current_timezone = new DateTimeZone('Asia/Colombo'); // Consistent timezone
$today = new DateTime('now', $current_timezone);

// Defaults for date range: current month
$default_start_date = (new DateTime('first day of this month', $current_timezone))->format('Y-m-d');
$default_end_date = $today->format('Y-m-d');

$start_date_str = sanitize_input(get_get_var('start_date', $default_start_date));
$end_date_str = sanitize_input(get_get_var('end_date', $default_end_date));

$page_title_dynamic_part = "Product Sales";
// Validate and format dates
try {
    $start_date_obj = new DateTime($start_date_str, $current_timezone);
    $end_date_obj = new DateTime($end_date_str, $current_timezone);

    // Ensure end_date is not before start_date
    if ($end_date_obj < $start_date_obj) {
        $end_date_obj = clone $start_date_obj; // Set end_date to start_date if invalid range
        set_flash_message('report_error', 'End date cannot be before start date. Adjusted end date.');
    }

    $start_date_str_formatted = $start_date_obj->format('Y-m-d');
    $end_date_str_formatted = $end_date_obj->format('Y-m-d');
    $page_title_dynamic_part = "Product Sales from " . $start_date_obj->format('M d, Y') . " to " . $end_date_obj->format('M d, Y');

} catch (Exception $e) {
    $start_date_obj = new DateTime($default_start_date, $current_timezone);
    $end_date_obj = new DateTime($default_end_date, $current_timezone);
    $start_date_str_formatted = $start_date_obj->format('Y-m-d');
    $end_date_str_formatted = $end_date_obj->format('Y-m-d');
    set_flash_message('report_error', 'Invalid date(s) provided, showing report for the default range.');
    $page_title_dynamic_part = "Product Sales from " . $start_date_obj->format('M d, Y') . " to " . $end_date_obj->format('M d, Y');
}
$page_title = $page_title_dynamic_part;


// Aggregate product sales within the date range
$product_sales_summary = [];

foreach ($all_sales_data as $sale) {
    if (isset($sale['date'])) {
        $sale_date_obj = new DateTime($sale['date'], $current_timezone); // Assuming sale dates are stored without specific time for this comparison logic
        
        // Compare dates only (ignoring time part for range inclusion)
        if ($sale_date_obj->format('Y-m-d') >= $start_date_str_formatted && $sale_date_obj->format('Y-m-d') <= $end_date_str_formatted) {
            if (isset($sale['items']) && is_array($sale['items'])) {
                foreach ($sale['items'] as $item) {
                    $product_id = $item['product_id'] ?? 'unknown';
                    $product_name = $item['name'] ?? 'Unknown Product';
                    $quantity = (int)($item['quantity'] ?? 0);
                    $item_total_revenue = (float)($item['total_price'] ?? 0); // Revenue from this item in this sale
                    $unit = $item['unit'] ?? '';

                    if ($quantity > 0) {
                        if (!isset($product_sales_summary[$product_id])) {
                            $product_sales_summary[$product_id] = [
                                'id' => $product_id,
                                'name' => $product_name,
                                'total_quantity_sold' => 0,
                                'total_revenue_generated' => 0,
                                'unit' => $unit // Assume unit is consistent for a product
                            ];
                        }
                        $product_sales_summary[$product_id]['total_quantity_sold'] += $quantity;
                        $product_sales_summary[$product_id]['total_revenue_generated'] += $item_total_revenue;
                    }
                }
            }
        }
    }
}

// Optional: Sort products (e.g., by name, by quantity sold, by revenue)
// Sort by product name for now
uasort($product_sales_summary, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4 md:mb-0">
                <?php echo $page_title; ?>
            </h1>
        </div>
        
        <form action="product_sales_report.php" method="GET" class="mb-8 p-4 bg-gray-50 rounded-lg shadow space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date:</label>
                <input type="date" name="start_date" id="start_date"
                       value="<?php echo $start_date_str_formatted; ?>"
                       class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date:</label>
                <input type="date" name="end_date" id="end_date"
                       value="<?php echo $end_date_str_formatted; ?>"
                       class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <button type="submit" class="w-full md:w-auto bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150">
                Generate Report
            </button>
        </form>

        <?php display_flash_message('report_error', 'error'); ?>

        <?php if (empty($product_sales_summary)): ?>
            <div class="text-center text-gray-500 py-10">
                 <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-4 text-xl">No product sales data found for the selected period.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto border border-gray-200 rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Quantity Sold</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($product_sales_summary as $product_id => $summary): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo sanitize_input($summary['id']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitize_input($summary['name']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700"><?php echo sanitize_input($summary['total_quantity_sold']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo sanitize_input($summary['unit']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-800 font-semibold">
                                    <?php echo format_price((float)$summary['total_revenue_generated'], $currency_symbol); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>