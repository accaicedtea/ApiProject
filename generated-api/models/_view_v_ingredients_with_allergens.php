<?php
require_once __DIR__ . '/../config/database.php';

class _view_v_ingredients_with_allergens {
    private $conn;
    private $query = 'SELECT i.name AS ingredient_name, a.id as azienda_id, i.description, i.image, i.price, i.unit, GROUP_CONCAT(DISTINCT CONCAT(al.name, \':\', COALESCE(al.icon, \'\')) ORDER BY al.name SEPARATOR \'|\') AS allergens, COUNT(DISTINCT pi.product_id) AS used_in_products 
FROM ingredients i 
LEFT JOIN ingredient_allergens ia ON i.id = ia.ingredient_id 
LEFT JOIN allergens al ON ia.allergen_id = al.id 
LEFT JOIN product_ingredients pi ON i.id = pi.ingredient_id 
LEFT JOIN azienda a ON a.id = al.azienda_id
GROUP BY i.name, i.description, i.image, i.price, i.unit, i.is_available, a.id
HAVING i.is_available=1';

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
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE ingredient_name = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt;
    }

    // Filtra per azienda_id dell'utente autenticato
    public function getAllByAzienda($azienda_id) {
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE azienda_id = ? ORDER BY ingredient_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $azienda_id);
        $stmt->execute();
        return $stmt;
    }

    public function getByIdByAzienda($id, $azienda_id) {
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE ingredient_name = ? AND azienda_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $azienda_id);
        $stmt->execute();
        return $stmt;
    }
}
