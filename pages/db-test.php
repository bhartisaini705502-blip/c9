<?php
/**
 * Database Connection Test Page
 * For debugging database connectivity
 */

require '../config/db.php';

$test_results = [
    'DB_HOST' => DB_HOST,
    'DB_USER' => DB_USER,
    'DB_NAME' => DB_NAME,
    'DB_PORT' => DB_PORT,
    'Connection Status' => DB_UNAVAILABLE ? 'UNAVAILABLE (Using Demo Data)' : 'CONNECTED',
];

if (!DB_UNAVAILABLE) {
    try {
        global $conn;
        $result = $conn->query('SELECT VERSION() as version');
        if ($result) {
            $row = $result->fetch_assoc();
            $test_results['MySQL Version'] = $row['version'];
            $test_results['Database Status'] = '✅ Successfully connected and responding';
        }
        
        // Check if businesses table exists
        $result = $conn->query("SHOW TABLES LIKE 'businesses'");
        if ($result && $result->num_rows > 0) {
            $test_results['Businesses Table'] = '✅ Table exists';
            
            // Count records
            $result = $conn->query('SELECT COUNT(*) as count FROM businesses');
            $row = $result->fetch_assoc();
            $test_results['Business Records'] = $row['count'] . ' records found';
        } else {
            $test_results['Businesses Table'] = '⚠️ Table not found - run setup/database.sql to create it';
        }
    } catch (Exception $e) {
        $test_results['Error'] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .test-result {
            margin: 15px 0;
            padding: 12px;
            border-left: 4px solid #ddd;
            background: #fafafa;
        }
        .test-result.success {
            border-left-color: #22c55e;
            background: #f0fdf4;
        }
        .test-result.warning {
            border-left-color: #eab308;
            background: #fffbeb;
        }
        .test-result.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #333;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Connection Test</h1>
        
        <?php foreach ($test_results as $label => $value): ?>
            <div class="test-result <?php 
                if (strpos($value, '✅') !== false) echo 'success';
                elseif (strpos($value, '⚠️') !== false) echo 'warning';
                elseif (strpos($value, 'Error') !== false || strpos($value, 'UNAVAILABLE') !== false) echo 'error';
            ?>">
                <span class="label"><?php echo htmlspecialchars($label); ?>:</span>
                <span class="value"><?php echo htmlspecialchars($value); ?></span>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <h2>Next Steps</h2>
            <?php if (DB_UNAVAILABLE): ?>
                <p style="color: #d97706;">
                    <strong>⚠️ Database Connection Failed:</strong> The application is currently using demo sample data.
                </p>
                <ol>
                    <li>Verify the database credentials in <code>config/db.php</code></li>
                    <li>Check Hostinger firewall allows remote connections (may need to add Replit IP to whitelist)</li>
                    <li>Ensure the database exists and is accessible</li>
                    <li>If using Hostinger, you may need to enable remote access in your hosting panel</li>
                </ol>
            <?php else: ?>
                <p style="color: #22c55e;">
                    <strong>✅ Database Connected Successfully!</strong>
                </p>
                <ol>
                    <li>Import the schema: <code>setup/database.sql</code></li>
                    <li>Add your business data to the database</li>
                    <li>The application will automatically use your real data</li>
                </ol>
            <?php endif; ?>
            
            <hr style="margin: 20px 0;">
            <p><a href="/" style="color: #667eea; text-decoration: none;">← Back to Homepage</a></p>
        </div>
    </div>
</body>
</html>
