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
            cart.product_id,
            products.label,
            products.category,
            products.image,
            products.price AS unit_price,
            cart.quantity,
            (cart.quantity * products.price) AS total_price
            FROM ' . $this->table . ' cart JOIN products ON cart.product_id = products.product_id
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
    public function add_product_to_cart(int $user_id, int $product_id, int $quantity)
    {
        if ($quantity <= 0) {
            return false;
        }

        $check_query = 'SELECT quantity FROM ' . $this->table . ' WHERE user_id = ? AND product_id = ?  ';
        $check_stmt = $this->conn->prepare($check_query);

        if (!$check_stmt) {
            return false;
        }
        $check_stmt->bind_param('ii', $user_id, $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $current_quantity = (int)$row['quantity'];
            $new_quantity = $current_quantity + $quantity;

            return $this->update_cart($user_id, $product_id, $new_quantity);
        } else {
            $insert_query = 'INSERT INTO ' . $this->table . ' (user_id, product_id, quantity) VALUES (?, ?, ?)';
            $insert_stmt = $this->conn->prepare($insert_query);
            if (!$insert_stmt) return false;

            $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
            return $insert_stmt->execute();
        }
    }


    //Menge eines Produkte ändern
    public function update_cart(int $user_id, int  $product_id, int $quantity)
    {
        if ($quantity <= 0) {
            return $this->remove_product_from_cart($user_id, $product_id);
        }
        $query = 'UPDATE ' . $this->table . ' SET quantity = ? WHERE user_id = ? AND product_id = ?';
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        return $stmt->execute();
    }

    //Produkt komplett aus dem Warenkorb entfernen
    public function remove_product_from_cart(int $user_id, int $product_id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE   user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("ii", $user_id, $product_id);
        return $stmt->execute();
    }
}
