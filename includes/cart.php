<?php
class Cart
{
    private $conn;
    private $table = "cart";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    //Alle Producte in im Warenkorb eines bestimmten Users
    public function get_user_cart(int $user_id)
    {
        $query = 'SELECT 
            cart.prduct_id,
            products.label,
            products.category,
            products.image,
            products.price AS unit_price,
            cart.quantity,
            (cart.quantity * unit_price) AS total_price
            FROM ' . $this->table . ' cart JOIN products ON cart.product_id = products_id
            WHERE cart.user_id = ?';

        $stmt = $this->conn->prepare($query);


        if (!$stmt) {
            return ["error" => $this->conn->error];
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $cart_items = [];

        while ($row = $result->fetch_assoc()) {
            $row['product_id'] = (int)$row['product_id'];
            $row['unit_price'] = (float)$row['unit_price'];
            $row['quantity'] = (int)$row['quantity'];
            $row['total_item_price'] = (float)$row['total_item_price'];
            
            $cart_items[] = $row;
        }

        return $cart_items;
    }

    //Ein Produkt in den Warencorb hinzufügen


    }
