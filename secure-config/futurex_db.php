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
    'DB_HOST' => 'sql301.infinityfree.com',   // <-- change this
    'DB_USER' => 'if0_39993218',      // <-- change this
    'DB_PASS' => 'IloveCoDInDg573', // <-- change this (same as VPanel)
    'DB_NAME' => 'if0_39993218_FutureX', // <-- change this
    'DB_PORT' => 3306,
    'DB_CHARSET' => 'utf8mb4',
];
