<?php
namespace App\Models;

class OrdersModel extends Model
{
    public function getAllOrders()
    {
        $sql = "SELECT o.*, p.name as provider_name 
                FROM orders o
                JOIN provider p ON o.ID_Provider = p.ID_Provider
                ORDER BY o.order_date DESC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    public function getOrder($id)
    {
        $sql = "SELECT o.*, p.name as provider_name, p.contact_email, p.phone 
                FROM orders o
                JOIN provider p ON o.ID_Provider = p.ID_Provider
                WHERE o.ID_Orders = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }
    
    public function getOrderWithLines($id)
    {
        // Récupérer la commande avec les infos du fournisseur
        $order = $this->getOrder($id);
        
        if (!$order) {
            return null;
        }
        
        // Récupérer les lignes de commande avec les infos produit
        $sql = "SELECT ol.*, p.name as product_name, p.reference 
                FROM order_line ol
                JOIN product p ON ol.ID_Product = p.ID_Product
                WHERE ol.ID_Orders = ?";
                
        $stmt = $this->query($sql, [$id]);
        $order->lines = $stmt->fetchAll(\PDO::FETCH_OBJ);
        
        // Calculer le total
        $order->total = 0;
        foreach ($order->lines as $line) {
            $order->total += $line->qty * $line->unit_price;
        }
        
        return $order;
    }
    
    public function getAllOrdersWithLines()
    {
        $orders = $this->getAllOrders();
        
        foreach ($orders as &$order) {
            $sql = "SELECT ol.*, p.name as product_name, p.reference 
                    FROM order_line ol
                    JOIN product p ON ol.ID_Product = p.ID_Product
                    WHERE ol.ID_Orders = ?";
                    
            $stmt = $this->query($sql, [$order->ID_Orders]);
            $order->lines = $stmt->fetchAll(\PDO::FETCH_OBJ);
            
            // Calculer le total
            $order->total = 0;
            foreach ($order->lines as $line) {
                $order->total += $line->qty * $line->unit_price;
            }
        }
        
        return $orders;
    }
    
    public function getFilteredOrders($search, $status = null, $provider_id = null)
    {
        $search = "%$search%";
        $sql = "SELECT o.*, p.name as provider_name 
                FROM orders o
                JOIN provider p ON o.ID_Provider = p.ID_Provider
                WHERE (p.name LIKE :search OR o.status LIKE :search)";
        
        $params = [':search' => $search];
        
        // Filtrer par statut si spécifié
        if ($status) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $status;
        }
        
        // Filtrer par fournisseur si spécifié
        if ($provider_id) {
            $sql .= " AND o.ID_Provider = :provider_id";
            $params[':provider_id'] = $provider_id;
        }
        
        $sql .= " ORDER BY o.order_date DESC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function createOrders($order_date, $expected_date, $status, $ID_Provider)
    {
        $data = [
            "order_date" => $order_date,
            "expected_date" => $expected_date,
            "status" => $status,
            "ID_Provider" => $ID_Provider
        ];
        return $this->create("orders", $data);
    }
    
    public function updateOrders(int $id, $order_date, $expected_date, $status, $ID_Provider)
    {
        $current = $this->getOrder($id);   
        $data = [
            "order_date" => $order_date ?? $current->order_date,
            "expected_date" => $expected_date ?? $current->expected_date,
            "status" => $status ?? $current->status,
            "ID_Provider" => $ID_Provider ?? $current->ID_Provider
        ];
        return $this->update($id, "orders", $data, "ID_Orders");
    }
    
    public function deleteOrder(int $id)
    {
        // Supprimer d'abord les lignes de commande associées
        $sql = "DELETE FROM order_line WHERE ID_Orders = ?";
        $this->query($sql, [$id]);
        
        // Puis supprimer la commande
        return $this->delete($id, "orders", "ID_Orders");
    }
    
    public function getOrdersByStatus($status)
    {
        $sql = "SELECT o.*, p.name as provider_name 
                FROM orders o
                JOIN provider p ON o.ID_Provider = p.ID_Provider
                WHERE o.status = ?
                ORDER BY o.order_date DESC";
        $stmt = $this->query($sql, [$status]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    public function getOrdersByProvider($providerId)
    {
        $sql = "SELECT o.*, p.name as provider_name 
                FROM orders o
                JOIN provider p ON o.ID_Provider = p.ID_Provider
                WHERE o.ID_Provider = ?
                ORDER BY o.order_date DESC";
        $stmt = $this->query($sql, [$providerId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    public function countPendingOrders()
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        return $result->count;
    }
}
?>