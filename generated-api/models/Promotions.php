<?php
require_once __DIR__ . '/../config/database.php';

class Promotions {
    private $conn;
    private $table_name = "promotions";

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
                SET azienda_id=:azienda_id, name=:name, description=:description, discount_type=:discount_type, discount_value=:discount_value, start_date=:start_date, end_date=:end_date, is_active=:is_active, created_at=:created_at, updated_at=:updated_at";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":azienda_id", $data['azienda_id']);
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":discount_type", $data['discount_type']);
        $stmt->bindParam(":discount_value", $data['discount_value']);
        $stmt->bindParam(":start_date", $data['start_date']);
        $stmt->bindParam(":end_date", $data['end_date']);
        $stmt->bindParam(":is_active", $data['is_active']);
        $stmt->bindParam(":created_at", $data['created_at']);
        $stmt->bindParam(":updated_at", $data['updated_at']);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                SET azienda_id=:azienda_id, name=:name, description=:description, discount_type=:discount_type, discount_value=:discount_value, start_date=:start_date, end_date=:end_date, is_active=:is_active, created_at=:created_at, updated_at=:updated_at 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":azienda_id", $data['azienda_id']);
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":discount_type", $data['discount_type']);
        $stmt->bindParam(":discount_value", $data['discount_value']);
        $stmt->bindParam(":start_date", $data['start_date']);
        $stmt->bindParam(":end_date", $data['end_date']);
        $stmt->bindParam(":is_active", $data['is_active']);
        $stmt->bindParam(":created_at", $data['created_at']);
        $stmt->bindParam(":updated_at", $data['updated_at']);
        
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
