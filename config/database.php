<?php
/**
 * OBU Student Marketplace - Database Connection
 * PDO Connection handler with error resilience and multi-cloud compatibility
 */

class Database {
    private static ?PDO $instance = null;
    private static ?string $connectionError = null;

    // Database Credentials (Default for local XAMPP, overridden by Cloud Environment Variables)
    private static string $host = '127.0.0.1';
    private static string $port = '3306';
    private static string $dbname = 'obu_marketplace';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset = 'utf8mb4';
    private static bool $configInitialized = false;

    /**
     * Retrieve environment variable checking $_ENV, $_SERVER, and getenv()
     */
    private static function getEnvVal(array $keys): ?string {
        foreach ($keys as $k) {
            $val = $_ENV[$k] ?? ($_SERVER[$k] ?? null);
            if ($val === null || $val === false || $val === '') {
                $val = getenv($k);
            }
            if ($val !== null && $val !== false && $val !== '') {
                return trim((string)$val);
            }
        }
        return null;
    }

    /**
     * Load environment variables if running in cloud (Vercel / TiDB / Railway / Render)
     */
    private static function initConfig(): void {
        if (self::$configInitialized) return;

        // Check full connection string (DATABASE_URL / MYSQL_URL)
        $dbUrl = self::getEnvVal(['DATABASE_URL', 'MYSQL_URL', 'database_url', 'mysql_url']);
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

        // Host: matches DB_HOST, host, HOST, TIDB_HOST, MYSQLHOST
        $envHost = self::getEnvVal(['DB_HOST', 'host', 'HOST', 'TIDB_HOST', 'MYSQLHOST', 'MYSQL_HOST']);
        if (!empty($envHost)) self::$host = $envHost;

        // Port: matches DB_PORT, port, PORT, TIDB_PORT, MYSQLPORT
        $envPort = self::getEnvVal(['DB_PORT', 'port', 'PORT', 'TIDB_PORT', 'MYSQLPORT', 'MYSQL_PORT']);
        if (!empty($envPort)) self::$port = (string)$envPort;

        // Database name: matches DB_NAME, database, DATABASE, TIDB_DATABASE, MYSQLDATABASE
        $envDb = self::getEnvVal(['DB_NAME', 'database', 'DATABASE', 'DB_DATABASE', 'TIDB_DATABASE', 'MYSQLDATABASE', 'MYSQL_DATABASE']);
        if (!empty($envDb)) self::$dbname = $envDb;

        // Username: matches DB_USER, username, USERNAME, user, USER, TIDB_USER, MYSQLUSER
        $envUser = self::getEnvVal(['DB_USER', 'username', 'USERNAME', 'user', 'USER', 'DB_USERNAME', 'TIDB_USER', 'MYSQLUSER', 'MYSQL_USER']);
        if (!empty($envUser)) self::$username = $envUser;

        // Password: matches DB_PASS, password, PASSWORD, pass, PASS, TIDB_PASSWORD, MYSQLPASSWORD
        $envPass = self::getEnvVal(['DB_PASS', 'password', 'PASSWORD', 'pass', 'PASS', 'DB_PASSWORD', 'TIDB_PASSWORD', 'MYSQLPASSWORD', 'MYSQL_PASSWORD']);
        if ($envPass !== null) self::$password = (string)$envPass;

        self::$configInitialized = true;
    }

    /**
     * Build PDO options based on environment and SSL requirements
     */
    private static function getPdoOptions(bool $includeCa = true): array {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        // Remote cloud host (TiDB Cloud, Aiven, etc.) - require/negotiate SSL
        if (self::$host !== '127.0.0.1' && self::$host !== 'localhost') {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;

            if ($includeCa) {
                $caCandidates = [
                    __DIR__ . '/cacert.pem',
                    '/etc/ssl/certs/ca-certificates.crt',
                    '/etc/pki/tls/certs/ca-bundle.crt',
                    '/etc/ssl/ca-bundle.pem'
                ];
                foreach ($caCandidates as $caFile) {
                    if (is_file($caFile)) {
                        $options[PDO::MYSQL_ATTR_SSL_CA] = $caFile;
                        break;
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Get or create PDO singleton connection
     */
    public static function getConnection(): ?PDO {
        if (self::$instance === null) {
            self::initConfig();

            // Try configured dbname first, with automatic fallback across aliases
            $candidateDbs = array_unique([self::$dbname, 'obu_marketplace', 'obu-marketplace', 'university_marketplace']);

            foreach ($candidateDbs as $dbName) {
                $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . $dbName . ";charset=" . self::$charset;

                // Attempt 1: With SSL CA if cloud host
                try {
                    $options = self::getPdoOptions(true);
                    self::$instance = new PDO($dsn, self::$username, self::$password, $options);
                    self::$dbname = $dbName;
                    self::$connectionError = null;
                    return self::$instance;
                } catch (PDOException $e1) {
                    // Attempt 2: Fallback without SSL CA parameter (for environments where CA file is unreadable)
                    if (self::$host !== '127.0.0.1' && self::$host !== 'localhost') {
                        try {
                            $optionsNoCa = self::getPdoOptions(false);
                            self::$instance = new PDO($dsn, self::$username, self::$password, $optionsNoCa);
                            self::$dbname = $dbName;
                            self::$connectionError = null;
                            return self::$instance;
                        } catch (PDOException $e2) {
                            self::$connectionError = $e2->getMessage();
                        }
                    } else {
                        self::$connectionError = $e1->getMessage();
                    }
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
        $options = self::getPdoOptions(true);
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
