<?php
namespace App\Models;
use Database;

use PDO;
use PDOException;
use PDOStatement;
abstract class Model {
    protected $pdo;
    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    protected function query($sql, $params = array()): PDOStatement
    {
        try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        } catch (PDOException $e) {
            throw new \Exception("Erreur de préparation de la requête : " . $e->getMessage());
        }
        return $stmt;
    }
    protected function getAll($table)
    {
        $sql = "SELECT * FROM $table";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    protected function getByID($table, $id, $idname)
    {
        $sql = "SELECT * FROM $table WHERE $idname = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    protected function getByFilter($table, $name, $filter, $optional = null)
    {
        $sql = "SELECT * FROM $table WHERE $name LIKE :filter" .($optional?:'');
        $stmt = $this->query($sql, ['filter' => "%{$filter}%"]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    protected function create($table, $data)
    {   
        $params = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), "?"));
        $sql = "INSERT INTO $table ($params) VALUES ($placeholders)";
        return $this->query($sql, array_values( $data));
    }

    protected function update($id, $table, $data, $idname)
    {
        $params = implode(", ", array_map(fn($key) => "$key = ?", array_keys($data)));
        $sql = "UPDATE $table SET $params WHERE $idname = ?";
        return $this->query($sql, array_merge(array_values($data), [$id]));
    }

    protected function delete($id, $table, $idname)
    {
        $sql = "DELETE FROM $table WHERE $idname = ?";
        return $this->query($sql, [$id]);
    }
}