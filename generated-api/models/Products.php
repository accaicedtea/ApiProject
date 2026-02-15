<?php
require_once __DIR__ . '/../config/database.php';

class Products {
    private $conn;
    private $table_name = "products";

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
                SET azienda_id=:azienda_id, category_id=:category_id, name=:name, slug=:slug, description=:description, price=:price, image=:image, is_available=:is_available, is_featured=:is_featured, stock=:stock, notes=:notes, created_at=:created_at, updated_at=:updated_at";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":azienda_id", $data['azienda_id']);
        $stmt->bindParam(":category_id", $data['category_id']);
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":slug", $data['slug']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":price", $data['price']);
        $stmt->bindParam(":image", $data['image']);
        $stmt->bindParam(":is_available", $data['is_available']);
        $stmt->bindParam(":is_featured", $data['is_featured']);
        $stmt->bindParam(":stock", $data['stock']);
        $stmt->bindParam(":notes", $data['notes']);
        $stmt->bindParam(":created_at", $data['created_at']);
        $stmt->bindParam(":updated_at", $data['updated_at']);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                SET azienda_id=:azienda_id, category_id=:category_id, name=:name, slug=:slug, description=:description, price=:price, image=:image, is_available=:is_available, is_featured=:is_featured, stock=:stock, notes=:notes, created_at=:created_at, updated_at=:updated_at 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":azienda_id", $data['azienda_id']);
        $stmt->bindParam(":category_id", $data['category_id']);
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":slug", $data['slug']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":price", $data['price']);
        $stmt->bindParam(":image", $data['image']);
        $stmt->bindParam(":is_available", $data['is_available']);
        $stmt->bindParam(":is_featured", $data['is_featured']);
        $stmt->bindParam(":stock", $data['stock']);
        $stmt->bindParam(":notes", $data['notes']);
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
