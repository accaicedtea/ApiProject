<?php
require_once __DIR__ . '/../config/database.php';

class _view_v_products_full {
    private $conn;
    private $query = 'SELECT 
    p.id AS product_id,
    p.azienda_id,
    a.name AS azienda_name,
    p.name AS product_name,
    p.slug AS product_slug,
    p.description AS product_description,
    p.price,
    p.image,
    p.is_available,
    p.is_featured,
    p.stock,
    p.notes,
    c.id AS category_id,
    c.name AS category_name,
    c.slug AS category_slug,
    c.icon AS category_icon,
    c.color AS category_color,
    GROUP_CONCAT(DISTINCT CONCAT(al.id, \':\', al.name, \':\', COALESCE(al.icon, \'\')) ORDER BY al.name SEPARATOR \'|\') AS allergens,
    GROUP_CONCAT(DISTINCT CONCAT(i.id, \':\', i.name, \':\', i.price, \':\', pi.is_optional) ORDER BY i.name SEPARATOR \'|\') AS ingredients  
FROM products p
INNER JOIN azienda a ON p.azienda_id = a.id
INNER JOIN categories c ON p.category_id = c.id
LEFT JOIN product_allergens pa ON p.id = pa.product_id
LEFT JOIN allergens al ON pa.allergen_id = al.id
LEFT JOIN product_ingredients pi ON p.id = pi.product_id
LEFT JOIN ingredients i ON pi.ingredient_id = i.id
GROUP BY p.id, p.azienda_id, a.name, p.name, p.slug, p.description, p.price, p.image, 
         p.is_available, p.is_featured, p.stock, p.notes, c.id, c.name, c.slug, 
         c.icon, c.color, p.created_at, p.updated_at';

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
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE product_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt;
    }

    // Filtra per azienda_id dell'utente autenticato
    public function getAllByAzienda($azienda_id) {
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE azienda_id = ? ORDER BY product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $azienda_id);
        $stmt->execute();
        return $stmt;
    }

    public function getByIdByAzienda($id, $azienda_id) {
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE product_id = ? AND azienda_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $azienda_id);
        $stmt->execute();
        return $stmt;
    }
}
