<?php
require_once __DIR__ . '/../config/database.php';

class Audit_allergens {
    private $conn;
    private $table_name = "audit_allergens";

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
                SET allergen_id=:allergen_id, azienda_id=:azienda_id, action=:action, changed_by=:changed_by, old_values=:old_values, new_values=:new_values, changed_at=:changed_at";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":allergen_id", $data['allergen_id']);
        $stmt->bindParam(":azienda_id", $data['azienda_id']);
        $stmt->bindParam(":action", $data['action']);
        $stmt->bindParam(":changed_by", $data['changed_by']);
        $stmt->bindParam(":old_values", $data['old_values']);
        $stmt->bindParam(":new_values", $data['new_values']);
        $stmt->bindParam(":changed_at", $data['changed_at']);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                SET allergen_id=:allergen_id, azienda_id=:azienda_id, action=:action, changed_by=:changed_by, old_values=:old_values, new_values=:new_values, changed_at=:changed_at 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":allergen_id", $data['allergen_id']);
        $stmt->bindParam(":azienda_id", $data['azienda_id']);
        $stmt->bindParam(":action", $data['action']);
        $stmt->bindParam(":changed_by", $data['changed_by']);
        $stmt->bindParam(":old_values", $data['old_values']);
        $stmt->bindParam(":new_values", $data['new_values']);
        $stmt->bindParam(":changed_at", $data['changed_at']);
        
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
