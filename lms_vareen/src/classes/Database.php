<?php
/**
 * Database Class - PDO Connection Handler
 *
 * Reads credentials from src/config/database.php (production constants)
 * with safe local-development fallbacks if the config is not loaded.
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private $host;
    private $db_name;
    private $user;
    private $pass;
    private $pdo;

    public function __construct() {
        $this->host    = defined('DB_HOST') ? DB_HOST : 'localhost';
        $this->db_name = defined('DB_NAME') ? DB_NAME : 'vereen_academy';
        $this->user    = defined('DB_USER') ? DB_USER : 'root';
        $this->pass    = defined('DB_PASS') ? DB_PASS : '';
    }

    public function connect() {
        if ($this->user === '' || $this->pass === '' || $this->db_name === '') {
            error_log('LMS database connection blocked: incomplete database configuration.');
            throw new RuntimeException('Database configuration is incomplete.');
        }

        try {
            $this->pdo = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                $this->user,
                $this->pass,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                )
            );
            return $this->pdo;
        } catch (PDOException $e) {
            error_log('LMS database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed.', 0, $e);
        }
    }

        public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

        public function insert(string $table, array $data): int {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * UPDATE a table by primary-key / custom WHERE builder.
     * $where is a string like 'id = ?' and $params are the bound values
     * that follow the column-value array.
     */
    public function update(string $table, array $data, string $where, array $params = []): int {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = '`' . $col . '` = :' . $col;
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets);
        $sql .= $where !== '' ? ' WHERE ' . $where : '';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, $params));
        return (int) $stmt->rowCount();
    }

    /** Fetch multiple rows. */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function execute($stmt, $params = []) {
        return $stmt->execute($params);
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }

    /**
     * Singleton accessor so orchestration classes (e.g. Payment) share a
     * single connected Database instance. Returns a connected self.
     */
        public static function getInstance(): self {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
            $instance->connect();
        }
        return $instance;
    }
}
