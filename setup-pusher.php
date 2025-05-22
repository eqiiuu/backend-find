<?php
/**
 * Pusher Setup Script for Laravel
 * 
 * This script helps you configure your Laravel application for Pusher broadcasting.
 * Run this script with: php setup-pusher.php
 */

// Check if .env file exists
$envFile = __DIR__ . '/.env';
$envExampleFile = __DIR__ . '/.env.example';

if (!file_exists($envFile)) {
    echo "Error: .env file not found. Please create it first by copying .env.example.\n";
    exit(1);
}

echo "======================================\n";
echo "Pusher Setup for Laravel Broadcasting\n";
echo "======================================\n\n";

// Get Pusher credentials
echo "Please enter your Pusher credentials (from your Pusher dashboard):\n";
echo "App ID: ";
$appId = trim(fgets(STDIN));

echo "App Key: ";
$appKey = trim(fgets(STDIN));

echo "App Secret: ";
$appSecret = trim(fgets(STDIN));

echo "App Cluster (default: ap1): ";
$cluster = trim(fgets(STDIN));
$cluster = $cluster ?: 'ap1';

// Read current .env
$envContent = file_get_contents($envFile);

// Check if broadcasting driver is already set
$patterns = [
    '/^BROADCAST_DRIVER=.*$/m' => "BROADCAST_DRIVER=pusher",
    '/^PUSHER_APP_ID=.*$/m' => "PUSHER_APP_ID=$appId",
    '/^PUSHER_APP_KEY=.*$/m' => "PUSHER_APP_KEY=$appKey",
    '/^PUSHER_APP_SECRET=.*$/m' => "PUSHER_APP_SECRET=$appSecret",
    '/^PUSHER_APP_CLUSTER=.*$/m' => "PUSHER_APP_CLUSTER=$cluster",
];

$replaced = false;
foreach ($patterns as $pattern => $replacement) {
    if (preg_match($pattern, $envContent)) {
        $envContent = preg_replace($pattern, $replacement, $envContent);
        $replaced = true;
    }
}

// If variables don't exist, add them
if (!$replaced) {
    $envContent .= "\n# Pusher Configuration\n";
    $envContent .= "BROADCAST_DRIVER=pusher\n";
    $envContent .= "PUSHER_APP_ID=$appId\n";
    $envContent .= "PUSHER_APP_KEY=$appKey\n";
    $envContent .= "PUSHER_APP_SECRET=$appSecret\n";
    $envContent .= "PUSHER_HOST=\n";
    $envContent .= "PUSHER_PORT=443\n";
    $envContent .= "PUSHER_SCHEME=https\n";
    $envContent .= "PUSHER_APP_CLUSTER=$cluster\n";
}

// Write updated .env file
file_put_contents($envFile, $envContent);

echo "\nPusher credentials have been added to your .env file.\n";
echo "Make sure the following is set in your config/app.php:\n";
echo "- App\\Providers\\BroadcastServiceProvider::class is uncommented in the providers array\n\n";

echo "Broadcasting routes should be configured correctly in your app.\n";
echo "You can check this in your routes/api.php file:\n";
echo "- Broadcast::routes(['middleware' => ['auth:sanctum']]); should be present\n\n";

echo "Also ensure your config/broadcasting.php has the correct Pusher configuration.\n\n";

echo "Your frontend React Native app should be configured with:\n";
echo "- The same App Key: $appKey\n";
echo "- The same Cluster: $cluster\n";
echo "- Currently set in: frontend_find/src/config/pusherConfig.js\n\n";

echo "Setup complete! Happy broadcasting!\n";
