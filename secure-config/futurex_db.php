<?php
declare(strict_types=1);

/**
 * Private DB config (protected by .htaccess). Keep this in /htdocs/secure-config/
 * and never commit it to GitHub.
 *
 * Fill these with your InfinityFree MySQL details:
 * - Hostname: looks like sqlXXX.epizy.com
 * - Username: epiz_XXXXXXXX
 * - Password: your Control Panel (VPanel) password
 * - Database: epiz_XXXXXXXX_canasia (example)
 * - Port: usually 3306
 */

return [
    'DB_HOST' => 'mysql-18c6ecfc-futurexkorat.h.aivencloud.com',
    'DB_USER' => 'avnadmin',
    'DB_PASS' => 'your-aiven-password-here',
    'DB_NAME' => 'defaultdb',
    'DB_PORT' => 11035,
    'DB_CHARSET' => 'utf8mb4',
];
