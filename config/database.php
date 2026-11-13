<?php
/**
 * OBU Student Marketplace - Database Connection
 * PDO Connection handler with error resilience
 */

class Database {
    private static ?PDO $instance = null;
    private static ?string $connectionError = null;

    // Database Credentials (Default for XAMPP, auto-overridden by Cloud Environment Variables)
    private static string $host = '127.0.0.1';
    private static string $port = '3306';
    private static string $dbname = 'obu_marketplace';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset = 'utf8mb4';
    private static bool $configInitialized = false;

    /**
     * Load environment variables if running in cloud (Vercel / Railway / Render)
     */
    private static function initConfig(): void {
        if (self::$configInitialized) return;

        // Check full connection string (DATABASE_URL / MYSQL_URL)
        $dbUrl = getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: '');
        if (!empty($dbUrl)) {
            $parts = parse_url($dbUrl);
            if ($parts) {
                if (!empty($parts['host'])) self::$host = $parts['host'];
                if (!empty($parts['port'])) self::$port = (string)$parts['port'];
                if (!empty($parts['user'])) self::$username = $parts['user'];
                if (isset($parts['pass'])) self::$password = $parts['pass'];
                if (!empty($parts['path'])) self::$dbname = ltrim($parts['path'], '/');
                self::$configInitialized = true;
                return;
            }
        }

        // Check individual environment variables
        $envHost = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: '');
        if (!empty($envHost)) self::$host = $envHost;

        $envPort = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '');
        if (!empty($envPort)) self::$port = (string)$envPort;

        $envDb = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: '');
        if (!empty($envDb)) self::$dbname = $envDb;

        $envUser = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: '');
        if (!empty($envUser)) self::$username = $envUser;

        $envPass = getenv('DB_PASS');
        if ($envPass === false) $envPass = getenv('MYSQLPASSWORD');
        if ($envPass !== false) self::$password = (string)$envPass;

        self::$configInitialized = true;
    }

    /**
     * Get or create PDO singleton connection
     */
    public static function getConnection(): ?PDO {
        if (self::$instance === null) {
            self::initConfig();

            // Try configured dbname first, with automatic fallback across aliases
            $candidateDbs = array_unique([self::$dbname, 'obu_marketplace', 'obu-marketplace', 'university_marketplace']);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            // If remote cloud MySQL (non-localhost), disable strict SSL cert verification for serverless compatibility
            if (self::$host !== '127.0.0.1' && self::$host !== 'localhost') {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            foreach ($candidateDbs as $dbName) {
                $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . $dbName . ";charset=" . self::$charset;
                try {
                    self::$instance = new PDO($dsn, self::$username, self::$password, $options);
                    self::$dbname = $dbName;
                    self::$connectionError = null;
                    return self::$instance;
                } catch (PDOException $e) {
                    self::$connectionError = $e->getMessage();
                    self::$instance = null;
                }
            }
        }

        return self::$instance;
    }

    /**
     * Get raw server connection without dbname (used for installer)
     */
    public static function getServerConnection(): ?PDO {
        self::initConfig();
        $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";charset=" . self::$charset;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if (self::$host !== '127.0.0.1' && self::$host !== 'localhost') {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        try {
            return new PDO($dsn, self::$username, self::$password, $options);
        } catch (PDOException $e) {
            self::$connectionError = $e->getMessage();
            return null;
        }
    }

    /**
     * Check if database is currently reachable
     */
    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }

    /**
     * Get last connection error message if any
     */
    public static function getError(): ?string {
        if (self::$instance === null && self::$connectionError === null) {
            self::getConnection();
        }
        return self::$connectionError;
    }

    /**
     * Update configuration parameters dynamically if needed
     */
    public static function configure(string $host, string $dbname, string $username, string $password, string $port = '3306'): void {
        self::$host = $host;
        self::$dbname = $dbname;
        self::$username = $username;
        self::$password = $password;
        self::$port = $port;
        self::$instance = null;
    }
}

/**
 * Global helper function to get database instance
 */
function db(): ?PDO {
    return Database::getConnection();
}
