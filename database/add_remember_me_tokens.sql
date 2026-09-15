-- ════════════════════════════════════════════════════════════════
-- Remember Me Token Tables for Persistent Mobile Login
-- Allows users to stay logged in on mobile devices that may clear
-- sessions on background/refresh
-- ════════════════════════════════════════════════════════════════

-- Admin Remember Me Tokens
CREATE TABLE IF NOT EXISTS admin_remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_expires_at (expires_at)
);

-- Organization President Remember Me Tokens
CREATE TABLE IF NOT EXISTS president_remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    president_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (president_id) REFERENCES organization_presidents(id) ON DELETE CASCADE,
    INDEX idx_president_id (president_id),
    INDEX idx_expires_at (expires_at)
);

-- Youth Remember Me Tokens
CREATE TABLE IF NOT EXISTS youth_remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    youth_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (youth_id) REFERENCES youth_users(id) ON DELETE CASCADE,
    INDEX idx_youth_id (youth_id),
    INDEX idx_expires_at (expires_at)
);

-- Cleanup Procedure: Delete expired tokens (run periodically)
-- CALL cleanup_expired_tokens();
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS cleanup_expired_tokens()
BEGIN
    DELETE FROM admin_remember_tokens WHERE expires_at < NOW();
    DELETE FROM president_remember_tokens WHERE expires_at < NOW();
    DELETE FROM youth_remember_tokens WHERE expires_at < NOW();
END$$
DELIMITER ;
