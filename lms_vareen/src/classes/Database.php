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
        try {
            $this->pdo = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->user,
                $this->pass,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
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
