<?php
// modules/reports/daily_sales.php
$page_title = "Daily Sales Report";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php'; // Also includes functions.php and db_helpers.php

$sales_file = __DIR__ . '/../../data/sales.json';
$all_sales = read_json_file($sales_file);
$currency_symbol = $settings['currency_symbol'] ?? '$'; // From header.php

// Determine the report date
// Default to current server date (Asia/Colombo as per previous context)
$default_date = new DateTime('now', new DateTimeZone('Asia/Colombo'));
$report_date_str = sanitize_input(get_get_var('report_date', $default_date->format('Y-m-d')));

// Validate date format (basic)
try {
    $report_date_obj = new DateTime($report_date_str, new DateTimeZone('Asia/Colombo'));
    $report_date_str_formatted = $report_date_obj->format('Y-m-d');
    $page_title = "Daily Sales Report for " . $report_date_obj->format('M d, Y');
} catch (Exception $e) {
    // Fallback to default date if provided date is invalid
    $report_date_obj = $default_date;
    $report_date_str_formatted = $default_date->format('Y-m-d');
    $page_title = "Daily Sales Report for " . $report_date_obj->format('M d, Y');
    set_flash_message('report_error', 'Invalid date provided, showing report for today.');
}


// Filter sales for the selected date
$sales_for_day = [];
$total_revenue_for_day = 0;
$total_sales_count_for_day = 0;
$aggregated_items_sold = [];

foreach ($all_sales as $sale) {
    if (isset($sale['date']) && $sale['date'] === $report_date_str_formatted) {
        $sales_for_day[] = $sale;
        $total_revenue_for_day += (float)($sale['grand_total'] ?? 0);
        $total_sales_count_for_day++;

        if (isset($sale['items']) && is_array($sale['items'])) {
            foreach ($sale['items'] as $item) {
                $product_id = $item['product_id'] ?? 'unknown';
                $product_name = $item['name'] ?? 'Unknown Product';
                $quantity = (int)($item['quantity'] ?? 0);

                if (isset($aggregated_items_sold[$product_id])) {
                    $aggregated_items_sold[$product_id]['quantity'] += $quantity;
                } else {
                    $aggregated_items_sold[$product_id] = [
                        'name' => $product_name,
                        'quantity' => $quantity,
                        'unit' => $item['unit'] ?? ''
                    ];
                }
            }
        }
    }
}
arsort($aggregated_items_sold); // Sort by quantity, highest first - optional

?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4 md:mb-0">
                <?php echo $page_title; ?>
            </h1>
            <form action="daily_sales.php" method="GET" class="flex items-center space-x-2">
                <label for="report_date" class="text-sm font-medium text-gray-700">Select Date:</label>
                <input type="date" name="report_date" id="report_date"
                       value="<?php echo $report_date_str_formatted; ?>"
                       class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150">
                    View Report
                </button>
            </form>
        </div>

        <?php display_flash_message('report_error', 'error'); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-blue-50 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-blue-700">Total Sales Transactions</h3>
                <p class="text-3xl font-bold text-blue-900"><?php echo $total_sales_count_for_day; ?></p>
            </div>
            <div class="bg-green-50 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-green-700">Total Revenue</h3>
                <p class="text-3xl font-bold text-green-900"><?php echo format_price($total_revenue_for_day, $currency_symbol); ?></p>
            </div>
        </div>

        <div class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Transactions on <?php echo $report_date_obj->format('M d, Y'); ?></h2>
            <?php if (empty($sales_for_day)): ?>
                <p class="text-gray-500">No sales transactions found for this day.</p>
            <?php else: ?>
                <div class="overflow-x-auto border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trans. ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items Summary</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($sales_for_day as $sale): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo sanitize_input($sale['transaction_id'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo sanitize_input($sale['time'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?php
                                        $item_summary = [];
                                        if (isset($sale['items']) && is_array($sale['items'])) {
                                            foreach ($sale['items'] as $item) {
                                                $item_summary[] = ($item['quantity'] ?? 0) . 'x ' . ($item['name'] ?? 'Item');
                                            }
                                        }
                                        echo implode(', ', array_slice($item_summary, 0, 3)); // Show first 3 items
                                        if (count($item_summary) > 3) echo '...';
                                        if (empty($item_summary)) echo 'No items listed';
                                        ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-800 font-semibold">
                                        <?php echo format_price((float)($sale['grand_total'] ?? 0), $currency_symbol); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Aggregated Items Sold on <?php echo $report_date_obj->format('M d, Y'); ?></h2>
            <?php if (empty($aggregated_items_sold)): ?>
                <p class="text-gray-500">No items were sold on this day.</p>
            <?php else: ?>
                <div class="overflow-x-auto border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Quantity Sold</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($aggregated_items_sold as $item_detail): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitize_input($item_detail['name']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700"><?php echo sanitize_input($item_detail['quantity']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo sanitize_input($item_detail['unit']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>