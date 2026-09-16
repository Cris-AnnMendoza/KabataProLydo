<?php
// ── Timezone Configuration ────────────────────────────────
// Set timezone to Philippines (UTC+8) for accurate time comparisons
date_default_timezone_set('Asia/Manila');

// ── Session Configuration ────────────────────────────────
// Configure sessions before starting
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 0); // Browser session by default
ini_set('session.cookie_httponly', 1); // No JS access to cookies
ini_set('session.cookie_samesite', 'Lax'); // Allow cross-site session (needed for QR links)
ini_set('session.cookie_secure', !empty(getenv('RAILWAY_ENVIRONMENT'))); // Secure only on production
ini_set('session.use_strict_mode', 1); // Strict session ID mode for security

if (session_status() === PHP_SESSION_NONE) session_start();

// ══════════════════════════════════════════════════════════════
// DATABASE CONFIGURATION - SUPPORTS BOTH LOCAL AND PRODUCTION
// ══════════════════════════════════════════════════════════════

// Check if running on Railway (production) or localhost (development)
$isProduction = getenv('RAILWAY_ENVIRONMENT') !== false || getenv('DATABASE_URL') !== false;

if ($isProduction) {
    // Production: Use Railway environment variables
    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl) {
        // Parse DATABASE_URL (format: mysql://user:password@host:port/database)
        $url = parse_url($databaseUrl);
        define('DB_HOST', $url['host'] ?? 'localhost');
        define('DB_PORT', $url['port'] ?? '3306');
        define('DB_USER', $url['user'] ?? 'root');
        define('DB_PASS', $url['pass'] ?? '');
        define('DB_NAME', ltrim($url['path'] ?? '/railway', '/'));
    } else {
        // Fallback to individual environment variables from Railway
        define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
        define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');
        define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
        define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
        define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway');
    }
} else {
    // Development: Use localhost XAMPP MySQL
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'local_youth_development_db');
}

// ── Gmail SMTP Config ─────────────────────────────────────
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'REMOVED_FOR_SECURITY');
define('MAIL_PASSWORD', 'REMOVED_FOR_SECURITY');
define('MAIL_FROM',     'REMOVED_FOR_SECURITY');
define('MAIL_FROM_NAME','LYDO Sta. Cruz, Laguna');

// ═════════════════════════════════════════════════════════════
// AI CONFIGURATION - FOR WELL-BEING ASSISTANT
// ══════════════════════════════════════════════════════════════
// Get free API keys from:
// - Groq: https://console.groq.com (RECOMMENDED - Fast & Free)
// - Gemini: https://makersuite.google.com/app/apikey
// REMOVED FOR SECURITY - Keys now in .env file (not in git)
// putenv('GROQ_API_KEY=REMOVED_FOR_SECURITY');
// putenv('GEMINI_API_KEY=REMOVED_FOR_SECURITY');

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;color:#c62828;background:#ffebee;border-radius:10px;max-width:600px;margin:40px auto">
                <h2>Database Connection Failed</h2>
                <p>'.$e->getMessage().'</p>
                <p>Make sure <strong>MySQL is running</strong> in XAMPP Control Panel.</p>
            </div>');
        }
    }
    return $pdo;
}

function flash(string $key, string $msg = ''): string {
    if ($msg) { $_SESSION['flash'][$key] = $msg; return ''; }
    $val = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $val;
}

/**
 * Get the server host that's accessible from mobile devices
 * Converts localhost to actual IP address for QR codes and mobile access
 */
function getAccessibleHost(): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // If localhost, try to get server's actual IP
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        // Try Windows ipconfig method first
        if (stristr(PHP_OS, 'WIN')) {
            exec('ipconfig', $output);
            foreach($output as $line) {
                if (strpos($line, 'IPv4') !== false) {
                    $parts = explode(':', $line);
                    if (isset($parts[1])) {
                        $ip = trim($parts[1]);
                        // Check if it's a local network IP
                        if (preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/i', $ip)) {
                            return $ip;
                        }
                    }
                }
            }
        }
        
        // Fallback: try gethostbyname
        $serverIP = gethostbyname(gethostname());
        if ($serverIP && $serverIP !== gethostname() && $serverIP !== '127.0.0.1') {
            return $serverIP;
        }
    }
    
    return $host;
}

function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $host     = MAIL_HOST;
    $port     = MAIL_PORT;
    $username = MAIL_USERNAME;
    $password = MAIL_PASSWORD;
    $from     = MAIL_FROM;
    $fromName = MAIL_FROM_NAME;

    if (empty($username) || empty($password) || $password === 'your_app_password_here') {
        error_log('LYDO Mail: Gmail credentials not configured in shared/config.php');
        return false;
    }

    try {
        $socket = fsockopen($host, $port, $errno, $errstr, 15);
        if (!$socket) {
            error_log("SMTP connect failed: $errstr ($errno)");
            return false;
        }
        stream_set_timeout($socket, 15);

        $read = function() use ($socket): string {
            $data = '';
            while ($line = fgets($socket, 515)) {
                $data .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $data;
        };

        $send = function(string $cmd) use ($socket, $read): string {
            fwrite($socket, $cmd . "\r\n");
            return $read();
        };

        $read();
        $send('EHLO ' . gethostname());
        $resp = $send('STARTTLS');
        if (strpos($resp, '220') === false) {
            fclose($socket);
            error_log('STARTTLS failed: ' . $resp);
            return false;
        }

        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $send('EHLO ' . gethostname());
        $send('AUTH LOGIN');
        $send(base64_encode($username));
        $authResp = $send(base64_encode($password));
        if (strpos($authResp, '235') === false) {
            fclose($socket);
            error_log('SMTP AUTH failed: ' . $authResp);
            return false;
        }

        $send("MAIL FROM:<{$from}>");
        $send("RCPT TO:<{$toEmail}>");
        $send('DATA');

        $boundary = md5(uniqid());
        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
        $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        $plainText = html_entity_decode($plainText, ENT_QUOTES, 'UTF-8');

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plainText)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $msgResp = $send($headers . "\r\n" . $body . "\r\n.");
        $send('QUIT');
        fclose($socket);

        if (strpos($msgResp, '250') !== false) {
            return true;
        }
        error_log('SMTP send failed: ' . $msgResp);
        return false;

    } catch (Throwable $e) {
        error_log('SMTP exception: ' . $e->getMessage());
        return false;
    }
}

/**
 * Handle "Remember Me" token for persistent login on mobile
 * Creates a secure token stored in database that can be used to re-authenticate
 */
function createRememberMeToken(string $userType, int $userId, int $expiryDays = 30): string {
    $token = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $token);
    $expiryDate = date('Y-m-d H:i:s', time() + ($expiryDays * 24 * 60 * 60));
    
    $pdo = db();
    
    if ($userType === 'admin') {
        $pdo->prepare('INSERT INTO admin_remember_tokens (admin_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())')
            ->execute([$userId, $hashedToken, $expiryDate]);
        // Set cookie: remember_me_admin=token|admin|userId
        setcookie('remember_me_token', "{$token}|admin|{$userId}", time() + ($expiryDays * 24 * 60 * 60), '/', '', false, true);
    } elseif ($userType === 'president') {
        $pdo->prepare('INSERT INTO president_remember_tokens (president_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())')
            ->execute([$userId, $hashedToken, $expiryDate]);
        setcookie('remember_me_token', "{$token}|president|{$userId}", time() + ($expiryDays * 24 * 60 * 60), '/', '', false, true);
    } elseif ($userType === 'youth') {
        $pdo->prepare('INSERT INTO youth_remember_tokens (youth_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())')
            ->execute([$userId, $hashedToken, $expiryDate]);
        setcookie('remember_me_token', "{$token}|youth|{$userId}", time() + ($expiryDays * 24 * 60 * 60), '/', '', false, true);
    }
    
    return $token;
}

/**
 * Verify Remember Me token and restore session if valid
 */
function verifyRememberMeToken(): bool {
    if (empty($_COOKIE['remember_me_token'])) {
        return false;
    }
    
    $parts = explode('|', $_COOKIE['remember_me_token']);
    if (count($parts) !== 3) {
        setcookie('remember_me_token', '', time() - 3600, '/');
        return false;
    }
    
    list($token, $userType, $userId) = $parts;
    
    // Validate token format before hashing
    if (empty($token) || !is_numeric($userId) || !in_array($userType, ['admin', 'president', 'youth'])) {
        setcookie('remember_me_token', '', time() - 3600, '/');
        return false;
    }
    
    $hashedToken = hash('sha256', $token);
    $pdo = db();
    
    if ($userType === 'admin') {
        $stmt = $pdo->prepare('SELECT * FROM admin_remember_tokens WHERE admin_id = ? AND token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([(int)$userId, $hashedToken]);
        $record = $stmt->fetch();
        
        if ($record) {
            // Restore session and verify admin is still active
            $adminStmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1');
            $adminStmt->execute([(int)$userId]);
            $admin = $adminStmt->fetch();
            
            if ($admin) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin'] = [
                    'id'        => $admin['id'],
                    'full_name' => $admin['full_name'],
                    'email'     => $admin['email'],
                    'role'      => $admin['role'],
                    'barangay'  => $admin['barangay'],
                ];
                return true;
            } else {
                // Admin was deactivated - clear token
                $pdo->prepare('DELETE FROM admin_remember_tokens WHERE admin_id = ?')->execute([(int)$userId]);
                setcookie('remember_me_token', '', time() - 3600, '/');
                return false;
            }
        }
    } elseif ($userType === 'president') {
        $stmt = $pdo->prepare('SELECT * FROM president_remember_tokens WHERE president_id = ? AND token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([(int)$userId, $hashedToken]);
        $record = $stmt->fetch();
        
        if ($record) {
            // Restore session and verify president is still active
            $presStmt = $pdo->prepare('SELECT op.*, o.name as organization_name FROM organization_presidents op JOIN organizations o ON o.id = op.organization_id WHERE op.id = ? AND op.is_active = 1 LIMIT 1');
            $presStmt->execute([(int)$userId]);
            $president = $presStmt->fetch();
            
            if ($president) {
                $_SESSION['org_president_id'] = $president['id'];
                $_SESSION['org_president'] = [
                    'id'                => $president['id'],
                    'organization_id'   => $president['organization_id'],
                    'organization_name' => $president['organization_name'],
                    'full_name'         => $president['full_name'],
                    'email'             => $president['email'],
                    'role'              => 'organization_president'
                ];
                return true;
            } else {
                // President was deactivated - clear token
                $pdo->prepare('DELETE FROM president_remember_tokens WHERE president_id = ?')->execute([(int)$userId]);
                setcookie('remember_me_token', '', time() - 3600, '/');
                return false;
            }
        }
    } elseif ($userType === 'youth') {
        $stmt = $pdo->prepare('SELECT * FROM youth_remember_tokens WHERE youth_id = ? AND token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([(int)$userId, $hashedToken]);
        $record = $stmt->fetch();
        
        if ($record) {
            // Restore session and verify youth is still approved
            $youthStmt = $pdo->prepare('SELECT * FROM youth_users WHERE id = ? AND status = ? LIMIT 1');
            $youthStmt->execute([(int)$userId, 'approved']);
            $user = $youthStmt->fetch();
            
            if ($user) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                return true;
            } else {
                // Youth user is no longer approved - clear token
                $pdo->prepare('DELETE FROM youth_remember_tokens WHERE youth_id = ?')->execute([(int)$userId]);
                setcookie('remember_me_token', '', time() - 3600, '/');
                return false;
            }
        }
    }
    
    // Token invalid or expired - clear cookie
    setcookie('remember_me_token', '', time() - 3600, '/');
    return false;
}
