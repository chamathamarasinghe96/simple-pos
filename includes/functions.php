<?php
// includes/functions.php

/**
 * Sanitizes string input to prevent XSS.
 * Should be used whenever displaying user-provided data.
 *
 * @param string|null $data The string to sanitize.
 * @return string The sanitized string.
 */
function sanitize_input(?string $data): string {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects to a given URL.
 *
 * @param string $url The URL to redirect to.
 * @return void
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Displays a formatted price.
 *
 * @param float $price The price to format.
 * @param string $currencySymbol The currency symbol (default is '$').
 * @return string The formatted price string.
 */
function format_price(float $price, string $currencySymbol = '$'): string {
    return $currencySymbol . number_format($price, 2);
}

/**
 * Get a value from POST request, with a default if not set.
 *
 * @param string $key The key to look for in $_POST.
 * @param mixed $default The default value to return if the key is not found.
 * @return mixed The value from POST or the default.
 */
function get_post_var(string $key, mixed $default = null): mixed {
    return $_POST[$key] ?? $default;
}

/**
 * Get a value from GET request, with a default if not set.
 *
 * @param string $key The key to look for in $_GET.
 * @param mixed $default The default value to return if the key is not found.
 * @return mixed The value from GET or the default.
 */
function get_get_var(string $key, mixed $default = null): mixed {
    return $_GET[$key] ?? $default;
}

/**
 * Helper to define base URL for links and assets.
 * Assumes the script is in a subdirectory of the web root or the web root itself.
 *
 * @return string The base URL of the application.
 */
function base_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // If your app is in a subfolder, you might need to adjust SCRIPT_NAME logic
    // For a simple setup where index.php is in the root of the app folder:
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    // Ensure it doesn't return just '\' if in web root
    $scriptName = ($scriptName == DIRECTORY_SEPARATOR) ? '' : $scriptName;
    return rtrim($protocol . $host . $scriptName, '/') . '/';
}

/* function base_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    
    // Assuming your project folder is named 'simple-pos' and is directly in the web root (e.g., htdocs/simple-pos)
    // If it's in a deeper subfolder, adjust this path.
    // If your project IS the web root, then $project_path would be an empty string.
    $project_path = '/simple-pos'; // CHANGE THIS if your folder name is different or it's not in a subfolder

    // If $_SERVER['SCRIPT_NAME'] already includes the project path (common)
    // we want to avoid duplicating it.

    // A simpler approach for known project folder:
    return rtrim($protocol . $host . $project_path, '/') . '/';

    // --- OR a more dynamic attempt (can be fragile if server config varies) ---
    // $script_name_parts = explode('/', $_SERVER['SCRIPT_NAME']);
    // $project_folder_name = 'simple-pos'; // Your project's root folder name
    // $path_segments = [];
    // foreach ($script_name_parts as $segment) {
    //     if (empty($segment)) continue;
    //     $path_segments[] = $segment;
    //     if ($segment == $project_folder_name) {
    //         break; // Stop once we've included the project folder
    //     }
    // }
    // $base_path = '/' . implode('/', $path_segments);
    // return rtrim($protocol . $host . $base_path, '/') . '/';
} */

/**
 * Displays a session-based flash message and then clears it.
 * Requires session_start() to be called on pages using it.
 *
 * @param string $key The key for the flash message.
 * @param string $type 'success', 'error', 'info', 'warning' for Tailwind CSS classes.
 * @return void
 */
function display_flash_message(string $key, string $type = 'info'): void {
    if (isset($_SESSION['flash_messages'][$key])) {
        $message = $_SESSION['flash_messages'][$key];
        unset($_SESSION['flash_messages'][$key]);

        $bgColor = 'bg-blue-100 border-blue-500 text-blue-700'; // default info
        if ($type === 'success') {
            $bgColor = 'bg-green-100 border-green-500 text-green-700';
        } elseif ($type === 'error') {
            $bgColor = 'bg-red-100 border-red-500 text-red-700';
        } elseif ($type === 'warning') {
            $bgColor = 'bg-yellow-100 border-yellow-500 text-yellow-700';
        }

        echo "<div class='{$bgColor} border-l-4 p-4 mb-4' role='alert'>";
        echo "<p class='font-bold'>" . ucfirst($type) . "</p>";
        echo "<p>" . sanitize_input($message) . "</p>";
        echo "</div>";
    }
}

/**
 * Sets a session-based flash message.
 * Requires session_start() to be called on pages using it.
 *
 * @param string $key The key for the flash message.
 * @param string $message The message content.
 * @return void
 */
function set_flash_message(string $key, string $message): void {
    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Start session if not already started
    }
    $_SESSION['flash_messages'][$key] = $message;
}

?>