<?php
/**
 * Database Class - PDO Connection Handler
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'vereen_academy';
    private $user = 'root';
    private $pass = '';
    private $pdo;

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
            die('Connection Error: ' . $e->getMessage());
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
