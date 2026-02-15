<?php
require_once __DIR__ . '/../config/database.php';

class Ingredient_allergens {
    private $conn;
    private $table_name = "ingredient_allergens";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt;
    }

    // Filtra per azienda_id dell'utente autenticato
    public function getAllByAzienda($azienda_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE azienda_id = ? ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $azienda_id);
        $stmt->execute();
        return $stmt;
    }

    public function getByIdByAzienda($id, $azienda_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? AND azienda_id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $azienda_id);
        $stmt->execute();
        return $stmt;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                SET ingredient_id=:ingredient_id, allergen_id=:allergen_id, created_at=:created_at";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":ingredient_id", $data['ingredient_id']);
        $stmt->bindParam(":allergen_id", $data['allergen_id']);
        $stmt->bindParam(":created_at", $data['created_at']);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                SET ingredient_id=:ingredient_id, allergen_id=:allergen_id, created_at=:created_at 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":ingredient_id", $data['ingredient_id']);
        $stmt->bindParam(":allergen_id", $data['allergen_id']);
        $stmt->bindParam(":created_at", $data['created_at']);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
