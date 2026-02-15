<?php
require_once __DIR__ . '/../config/database.php';

class _view_v_products_with_promotions {
    private $conn;
    private $query = 'SELECT 
    p.azienda_id,
    p.name AS product_name,
    p.slug AS product_slug,
    p.description AS product_description,
    p.price AS original_price,
    p.image,
    p.is_available,
    p.is_featured,
    c.name AS category_name,
    pr.id AS promotion_id,
    pr.name AS promotion_name,
    pr.discount_type,
    pr.discount_value,
    pr.start_date AS promotion_start,
    pr.end_date AS promotion_end,
    CASE 
        WHEN pr.discount_type = \'percentage\' THEN p.price - (p.price * pr.discount_value / 100)
        WHEN pr.discount_type = \'fixed\' THEN p.price - pr.discount_value
        ELSE p.price
    END AS discounted_price,
    CASE 
        WHEN pr.discount_type = \'percentage\' THEN ROUND(pr.discount_value, 0)
        WHEN pr.discount_type = \'fixed\' THEN ROUND((pr.discount_value / p.price) * 100, 0)
        ELSE 0
    END AS discount_percentage,
    CASE 
        WHEN pr.id IS NOT NULL AND NOW() BETWEEN pr.start_date AND pr.end_date AND pr.is_active = 1 THEN 1
        ELSE 0
    END AS has_active_promotion
FROM products p
INNER JOIN categories c ON p.category_id = c.id
LEFT JOIN product_promotions pp ON p.id = pp.product_id
LEFT JOIN promotions pr ON pp.promotion_id = pr.id
WHERE pr.azienda_id=c.azienda_id and pr.is_active=1';

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
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE azienda_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt;
    }

    // Filtra per azienda_id dell'utente autenticato
    public function getAllByAzienda($azienda_id) {
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE azienda_id = ? ORDER BY azienda_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $azienda_id);
        $stmt->execute();
        return $stmt;
    }

    public function getByIdByAzienda($id, $azienda_id) {
        $query = "SELECT * FROM (" . $this->query . ") AS view_result WHERE azienda_id = ? AND azienda_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $azienda_id);
        $stmt->execute();
        return $stmt;
    }
}
