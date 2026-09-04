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

    public function query($sql) {
        return $this->pdo->prepare($sql);
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
}
