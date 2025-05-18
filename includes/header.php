<?php
// includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start session if not already started, for flash messages etc.
}

require_once __DIR__ . '/functions.php'; // To use base_url(), sanitize_input()
require_once __DIR__ . '/db_helpers.php'; // To potentially load settings for shop name

// Load shop settings for title
// Define the path to the settings file relative to this header.php file
$settings_file_path = __DIR__ . '/../data/settings.json';
$settings = read_json_file($settings_file_path);
$shop_name = isset($settings['shop_name']) && !empty($settings['shop_name']) ? $settings['shop_name'] : 'Simple POS System';

// Page title logic: if $page_title is set on the specific page, use it, otherwise default.
$current_page_title = isset($page_title) ? sanitize_input($page_title) . ' - ' . sanitize_input($shop_name) : sanitize_input($shop_name);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/style.css">

    <style>
        /* Additional global styles or Tailwind component overrides can go here if needed */
        [x-cloak] { display: none !important; } /* Hide Alpine.js elements until initialized */

        /* Tailwind CSS @apply directives are best used in a compiled CSS file.
           For CDN usage, you'd typically use the direct utility classes in your HTML.
           However, if you want to define some reusable component styles here,
           you can, but they won't be processed by Tailwind's @apply.
           For simplicity with CDN, let's define simple CSS classes if needed, or rely on utilities in HTML.
        */
        .nav-link {
            padding: 0.5rem 0.75rem; /* Equivalent to px-3 py-2 */
            border-radius: 0.375rem; /* Equivalent to rounded-md */
            font-size: 0.875rem; /* Equivalent to text-sm */
            font-weight: 500; /* Equivalent to font-medium */
            color: #D1D5DB; /* Equivalent to text-gray-300 */
            transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
        }
        .nav-link:hover {
            background-color: #374151; /* Equivalent to hover:bg-gray-700 */
            color: #FFFFFF; /* Equivalent to hover:text-white */
        }
        .nav-link-active {
            background-color: #111827; /* Equivalent to bg-gray-900 */
            color: #FFFFFF; /* Equivalent to text-white */
        }

        .mobile-nav-link {
            display: block;
            padding: 0.5rem 0.75rem; /* Equivalent to px-3 py-2 */
            border-radius: 0.375rem; /* Equivalent to rounded-md */
            font-size: 1rem; /* Equivalent to text-base */
            font-weight: 500; /* Equivalent to font-medium */
            color: #D1D5DB; /* Equivalent to text-gray-300 */
            transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
        }
        .mobile-nav-link:hover {
            background-color: #374151; /* Equivalent to hover:bg-gray-700 */
            color: #FFFFFF; /* Equivalent to hover:text-white */
        }
        .mobile-nav-link-active {
            background-color: #111827; /* Equivalent to bg-gray-900 */
            color: #FFFFFF; /* Equivalent to text-white */
        }

    </style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-gray-800 shadow-lg" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="relative flex items-center justify-between h-16">
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?php echo base_url(); ?>index.php" class="text-white text-xl font-bold">
                        <?php echo sanitize_input($shop_name); ?>
                    </a>
                </div>

                <div class="hidden sm:ml-6 sm:flex sm:space-x-4">
                    <?php
                    $current_script_name = basename($_SERVER['PHP_SELF']);
                    $active_class_desktop = 'nav-link nav-link-active';
                    $inactive_class_desktop = 'nav-link';

                    $nav_items = [
                        "Dashboard" => "index.php",
                        "New Sale" => "modules/sales/new_sale.php",
                        "Products" => "modules/products/list_products.php",
                        "Inventory" => "modules/inventory/view_stock.php",
                        "Sales History" => "modules/sales/view_sales_history.php",
                        "Reports" => "modules/reports/daily_sales.php", // First report page
                        "Settings" => "modules/settings/manage_settings.php"
                    ];

                    // Define pages that belong to a "section" for highlighting
                    $product_pages = ['list_products.php', 'add_product.php', 'edit_product.php'];
                    $report_pages = ['daily_sales.php', 'product_sales_report.php'];

                    foreach ($nav_items as $label => $link_target_raw) {
                        $is_active = false;
                        $link_target = base_url() . $link_target_raw;
                        $link_script_name = basename($link_target_raw);

                        if ($label === "Products" && in_array($current_script_name, $product_pages)) {
                            $is_active = true;
                        } elseif ($label === "Reports" && in_array($current_script_name, $report_pages)) {
                            $is_active = true;
                        } elseif ($current_script_name === $link_script_name || ($current_script_name === '' && $link_script_name === 'index.php')) {
                             $is_active = true;
                        }
                        $class_to_apply = $is_active ? $active_class_desktop : $inactive_class_desktop;
                        echo "<a href='{$link_target}' class='{$class_to_apply}'>{$label}</a>";
                    }
                    ?>
                </div>

                <div class="sm:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg x-show="!mobileMenuOpen" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

         <div x-show="mobileMenuOpen" x-cloak class="sm:hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                 <?php
                    $active_class_mobile = 'mobile-nav-link mobile-nav-link-active';
                    $inactive_class_mobile = 'mobile-nav-link';

                    foreach ($nav_items as $label => $link_target_raw) {
                        $is_active = false;
                        $link_target = base_url() . $link_target_raw;
                        $link_script_name = basename($link_target_raw);

                        if ($label === "Products" && in_array($current_script_name, $product_pages)) {
                            $is_active = true;
                        } elseif ($label === "Reports" && in_array($current_script_name, $report_pages)) {
                            $is_active = true;
                        } elseif ($current_script_name === $link_script_name || ($current_script_name === '' && $link_script_name === 'index.php')) {
                             $is_active = true;
                        }
                        $class_to_apply = $is_active ? $active_class_mobile : $inactive_class_mobile;
                        echo "<a href='{$link_target}' class='{$class_to_apply}'>{$label}</a>";
                    }
                    ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4 mt-4">
        <?php
        // Assuming flash messages are stored with keys like 'success_message', 'error_message'
        if (isset($_SESSION['flash_messages'])) {
            foreach ($_SESSION['flash_messages'] as $key => $message) {
                $type = 'info'; // default
                if (strpos($key, 'success') !== false) $type = 'success';
                if (strpos($key, 'error') !== false) $type = 'error';
                if (strpos($key, 'warning') !== false) $type = 'warning';
                display_flash_message($key, $type); // This function now needs to be aware of the session directly or key passed
            }
            // It's better if display_flash_message unsets the specific key it displays.
            // If set_flash_message and display_flash_message manage $_SESSION['flash_messages'][$key] directly,
            // then after iterating and displaying, we might clear the whole array if needed, or handle individually.
            // For this version, assuming display_flash_message handles unsetting.
        }
        ?>
    </div>
</body>