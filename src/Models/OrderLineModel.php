<?php
namespace App\Models;

class OrderLineModel extends Model
{
    public function getAllOrderLines()
    {
        return $this->getAll("order_line");
    }
    
    public function getOrderLinesByOrder($orderId)
    {
        $sql = "SELECT ol.*, p.name as product_name 
                FROM order_line ol
                JOIN product p ON ol.ID_Product = p.ID_Product
                WHERE ol.ID_Orders = ?";
        $stmt = $this->query($sql, [$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    public function getOrderLine($orderId, $productId)
    {
        $sql = "SELECT * FROM order_line WHERE ID_Orders = ? AND ID_Product = ?";
        $stmt = $this->query($sql, [$orderId, $productId]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }
    
    public function getFilteredOrderLines($search)
    {
        $search = "%$search%";
        $sql = "SELECT ol.*, p.name as product_name 
                FROM order_line ol
                JOIN product p ON ol.ID_Product = p.ID_Product
                WHERE p.name LIKE :search OR p.reference LIKE :search";
        $params = [
            ':search' => $search,
        ];
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function createOrderLine($ID_Orders, $ID_Product, $qty, $unit_price)
    {
        $data = [
            "ID_Orders" => $ID_Orders,
            "ID_Product" => $ID_Product,
            "qty" => $qty,
            "unit_price" => $unit_price
        ];
        
        // Personnalisation pour une table avec clé composée
        $sql = "INSERT INTO order_line (ID_Orders, ID_Product, qty, unit_price) 
                VALUES (:ID_Orders, :ID_Product, :qty, :unit_price)";
                
        return $this->query($sql, $data);
    }
    
    public function updateOrderLine($ID_Orders, $ID_Product, $qty, $unit_price)
    {
        $current = $this->getOrderLine($ID_Orders, $ID_Product);
        
        $data = [
            "qty" => $qty ?? $current->qty,
            "unit_price" => $unit_price ?? $current->unit_price,
            "ID_Orders" => $ID_Orders,
            "ID_Product" => $ID_Product
        ];
        
        // Personnalisation pour une table avec clé composée
        $sql = "UPDATE order_line 
                SET qty = :qty, unit_price = :unit_price 
                WHERE ID_Orders = :ID_Orders AND ID_Product = :ID_Product";
                
        return $this->query($sql, $data);
    }
    
    public function deleteOrderLine($ID_Orders, $ID_Product)
    {
        $sql = "DELETE FROM order_line WHERE ID_Orders = ? AND ID_Product = ?";
        return $this->query($sql, [$ID_Orders, $ID_Product]);
    }
    
    public function deleteAllOrderLines($ID_Orders)
    {
        $sql = "DELETE FROM order_line WHERE ID_Orders = ?";
        return $this->query($sql, [$ID_Orders]);
    }
    
    public function calculateOrderTotal($ID_Orders)
    {
        $sql = "SELECT SUM(qty * unit_price) as total FROM order_line WHERE ID_Orders = ?";
        $stmt = $this->query($sql, [$ID_Orders]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        return $result ? $result->total : 0;
    }
}
?>