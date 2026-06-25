<?php
class Products
{
    private $conn;
    private $table = "products";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    //Alle Prudukte anzeigen
    public function get_products()
    {
        $query = 'SELECT product_id, category, label, description, stock, price, image FROM ' . $this->table;
        $result = $this->conn->query($query);

        if (!$result) {
            return [
                "error" => $this->conn->error
            ];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    //Product hinzufügen
    public function create_products(string $category, string $label, string $description, int $stock, float $price, string $image)
    {
        $query = 'INSERT INTO ' . $this->table . '(category,label,description,stock,price,image) VALUES (?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return [
                "success" => false,
                "error" => "Prepare fehlgeschlagen: " . $this->conn->error
            ];
        }


        $stmt->bind_param('sssids', $category, $label, $description, $stock, $price, $image);
        if (!$stmt->execute()) {
            return [
                "success" => false,
                "error" => "Execute fehlgeschlagen: " . $stmt->error
            ];
        }

        return [
            "success" => true,
            "product_id" => $stmt->insert_id
        ];
    }

    //Produkt suche/anzeigen mit dem Namen
    public function get_one_products(string $label)
    {
        $query = 'SELECT product_id, category, label, description, stock, price, image FROM ' . $this->table . ' WHERE label LIKE ?';
        $stmt = $this->conn->prepare($query);
        $search_parameter = "%" . $label . "%";
        $stmt->bind_param("s", $search_parameter);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        return $products;
    }

    //alle Produkte anzeigen einer Kategorie
    public function get_product_by_category(string $category)
    {
        $query = 'SELECT product_id, category, label, description, stock, price, image FROM ' . $this->table . ' WHERE category = ?';
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        return $products;
    }

    //ein Product anzeigen mit der ID 
    public function get_product_by_id(int $id)
    {
        $query = 'SELECT product_id, category, label, description, stock, price, image FROM ' . $this->table . ' WHERE product_id = ?';
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    //Produkt Updaten
    public function update_products(int $product_id, string $category, string $label, string $description, int $stock, float $price, string $image)
    {
        $query = 'UPDATE ' . $this->table . " SET category = ?,label = ?,description = ?,stock = ?, price = ?,image = ? WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssidsi", $category, $label, $description, $stock, $price, $image, $product_id);
        return $stmt->execute();
    }

    //Product löschen
    public function delete_product(int $product_id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE product_id=?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        return $stmt->execute();
    }
}
