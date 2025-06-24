<?php
// Log every time the file is accessed
file_put_contents("debug_access.txt", "File accessed at " . date("Y-m-d H:i:s") . "\n", FILE_APPEND);

// Read raw input
$input = file_get_contents("php://input");

// Log the raw JSON input
file_put_contents("debug_input.txt", $input);

// Only proceed if it's a POST request with content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($input)) {
    $data = json_decode($input, true);

    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON Decode Error: " . json_last_error_msg();
    } else {
        // Log decoded JSON
        file_put_contents("debug_log_parsed.txt", print_r($data, true));
        echo "✅ JSON parsed successfully.";
    }
} else {
    echo "🟡 No POST JSON received. Method: " . $_SERVER['REQUEST_METHOD'];
}
?>
