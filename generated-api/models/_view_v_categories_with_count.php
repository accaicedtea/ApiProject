<?php
require_once __DIR__ . '/../config/database.php';

class _view_v_categories_with_count {
    private $conn;
    private $query = 'SELECT 
    c.id AS category_id,
    c.azienda_id,
    a.name AS azienda_name,
    c.name AS category_name,
    c.slug AS category_slug,
    c.description,
    c.icon,
    c.color,
    c.sort_order,
    c.is_active,
    COUNT(p.id) AS products_count,
    COUNT(CASE WHEN p.is_available = 1 THEN 1 END) AS available_products_count,
    c.created_at,
    c.updated_at
FROM categories c
INNER JOIN azienda a ON c.azienda_id = a.id
LEFT JOIN products p ON c.id = p.category_id
GROUP BY c.id, c.azienda_id, a.name, c.name, c.slug, c.description, 
         c.icon, c.color, c.sort_order, c.is_active, c.created_at, c.updated_at';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $stmt = $this->conn->prepare($this->query);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id) {
        // Per le viste, getById filtra i risultati della query
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE category_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt;
    }

    // Filtra per azienda_id dell'utente autenticato
    public function getAllByAzienda($azienda_id) {
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE azienda_id = ? ORDER BY category_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $azienda_id);
        $stmt->execute();
        return $stmt;
    }

    public function getByIdByAzienda($id, $azienda_id) {
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE category_id = ? AND azienda_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $azienda_id);
        $stmt->execute();
        return $stmt;
    }
}
