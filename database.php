<?php
/**
 * data/database.php
 * Lớp kết nối CSDL PDO MySQL / MariaDB (hoặc sqlsrv)
 * Dự án Quản lý & Bán Đồng Hồ Pin (quanly_dongho)
 */

if (!defined('DB_DRIVER')) {
    define('DB_DRIVER', 'mysql'); // 'mysql' hoặc 'sqlsrv'
}
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', '3307'); // Cổng MariaDB XAMPP (3307 hoặc 3306)
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'quanly_dongho');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

class Database {
    private static ?PDO $instance = null;
    private static ?string $lastError = null;

    /**
     * Lấy kết nối PDO duy nhất (Singleton)
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            if (DB_DRIVER === 'mysql') {
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => true,
                ];

                // Thử cổng cấu hình trước, nếu lỗi kết nối thì thử cổng phụ (3307 <-> 3306)
                $portsToTry = [DB_PORT, (DB_PORT === '3307' ? '3306' : '3307')];
                $lastEx = null;

                foreach ($portsToTry as $port) {
                    try {
                        $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                        self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                        break;
                    } catch (PDOException $e) {
                        $lastEx = $e;
                    }
                }

                if (self::$instance === null && $lastEx !== null) {
                    self::$lastError = $lastEx->getMessage();
                    throw $lastEx;
                }
            } else {
                // Hỗ trợ chế độ SQL Server (sqlsrv)
                if (!extension_loaded('pdo_sqlsrv')) {
                    $err = "Extension 'pdo_sqlsrv' chưa được bật trong php.ini.";
                    self::$lastError = $err;
                    throw new PDOException($err);
                }

                $dsn = 'sqlsrv:Server=' . DB_HOST . ';Database=' . DB_NAME . ';TrustServerCertificate=true;Encrypt=false';
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => true,
                ];

                if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
                    $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
                }

                try {
                    self::$instance = new PDO($dsn, DB_USER !== '' ? DB_USER : null, DB_PASS !== '' ? DB_PASS : null, $options);
                } catch (PDOException $e) {
                    self::$lastError = $e->getMessage();
                    throw $e;
                }
            }
        }

        return self::$instance;
    }

    /**
     * Kiểm tra trạng thái kết nối CSDL hiện tại
     */
    public static function checkConnection(): array {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query("SELECT VERSION() AS ver, DATABASE() AS current_db");
            $info = $stmt->fetch();
            return [
                'status'   => true,
                'message'  => 'Kết nối CSDL thành công!',
                'version'  => $info['ver'] ?? 'Unknown',
                'database' => $info['current_db'] ?? DB_NAME
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'message' => 'Lỗi kết nối CSDL: ' . $e->getMessage()
            ];
        }
    }
}
