<?php
/**
 * Quick verification page to test fixes
 */
require_once 'shared/config.php';

$pdo = db();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LYDO Fixes Verification</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-top: 0; }
        h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .status { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: 600; margin: 10px 0; }
        .status.pass { background: #d4edda; color: #155724; }
        .status.fail { background: #f8d7da; color: #721c24; }
        .status.warning { background: #fff3cd; color: #856404; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 16px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        ul { margin: 10px 0; padding-left: 20px; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 LYDO System - Fixes Verification</h1>
        
        <!-- FIX 1: Organization Dropdown -->
        <div class="card">
            <h2>✅ Fix 1: Organization Dropdown in Registration</h2>
            
            <?php
            // Check if organizations exist in database
            $orgCount = $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn();
            $activeCount = $pdo->query('SELECT COUNT(*) FROM organizations WHERE accreditation_status = "active"')->fetchColumn();
            ?>
            
            <div class="status <?php echo $orgCount > 0 ? 'pass' : 'fail'; ?>">
                <?php echo $orgCount > 0 ? '✓ PASS' : '✗ FAIL'; ?>: Database has organizations
            </div>
            
            <ul>
                <li><strong>Total Organizations:</strong> <?php echo $orgCount; ?></li>
                <li><strong>Active Organizations:</strong> <?php echo $activeCount; ?></li>
            </ul>
            
            <p><strong>What was fixed:</strong></p>
            <ul>
                <li>Removed duplicate <code>r_org_name</code> select element from Step 1</li>
                <li>Now only in Step 4 (Education section)</li>
                <li>JavaScript loadOrganizations() will populate correctly</li>
            </ul>
            
            <p><strong>How to test:</strong></p>
            <ol>
                <li>Go to <a href="index.html" target="_blank">landing page</a></li>
                <li>Click "Register" button</li>
                <li>Fill Step 1 (Personal Info)</li>
                <li>Go to Step 4 (Education)</li>
                <li>Check if "Select Your Organization" dropdown shows organizations ✓</li>
            </ol>
        </div>
        
        <!-- FIX 2: Chatbot API -->
        <div class="card">
            <h2>🤖 Fix 2: Chatbot API Connection</h2>
            
            <?php
            // Check environment variables
            $groqKey = getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? null);
            $aiKey = getenv('AI_API_KEY') ?: ($_ENV['AI_API_KEY'] ?? null);
            $hasKey = $groqKey || $aiKey;
            ?>
            
            <div class="status <?php echo $hasKey ? 'pass' : 'fail'; ?>">
                <?php echo $hasKey ? '✓ PASS' : '✗ FAIL'; ?>: Environment variables
            </div>
            
            <ul>
                <li><strong>GROQ_API_KEY:</strong> <?php echo $groqKey ? '✓ Set (' . substr($groqKey, 0, 15) . '...)' : '✗ Not found'; ?></li>
                <li><strong>AI_API_KEY:</strong> <?php echo $aiKey ? '✓ Set (' . substr($aiKey, 0, 15) . '...)' : '✗ Not found'; ?></li>
            </ul>
            
            <p><strong>What was improved:</strong></p>
            <ul>
                <li>Support for both <code>getenv()</code> and <code>$_ENV</code> access</li>
                <li>Better fallback chain: GROQ_API_KEY → AI_API_KEY</li>
                <li>Enhanced error logging to help debugging</li>
                <li>More detailed error messages in logs</li>
            </ul>
            
            <p><strong>How to test:</strong></p>
            <ol>
                <li>Log in to youth portal</li>
                <li>Go to Well-being Assistant</li>
                <li>Send a test message (e.g., "Hi")</li>
                <li>Check if chatbot responds ✓</li>
                <li>If not working, check Railway logs for error messages</li>
            </ol>
            
            <div class="status warning">
                ℹ️ INFO: If not working on Railway, check:
            </div>
            <ul>
                <li>Go to Railway Dashboard → Your Project → Logs</li>
                <li>Look for error messages from <code>wellbeing.php</code></li>
                <li>Check if HTTP status code indicates API rejection</li>
                <li>Verify API key is correctly set (no extra spaces)</li>
            </ul>
        </div>
        
        <!-- Summary -->
        <div class="card" style="background: #e8f5e9; border-left: 4px solid #2e7d32;">
            <h2 style="border-bottom-color: #2e7d32;">📋 Summary</h2>
            
            <p><strong>Completed Fixes:</strong></p>
            <ul>
                <li>✅ Organization dropdown duplicate removal</li>
                <li>✅ Chatbot API environment variable handling improved</li>
                <li>✅ Better error logging added</li>
            </ul>
            
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Test registration form - organizations should appear in Step 4</li>
                <li>Test chatbot in youth portal - should respond to messages</li>
                <li>If issues persist, check browser console (F12) and Railway logs</li>
            </ol>
        </div>
    </div>
</body>
</html>
