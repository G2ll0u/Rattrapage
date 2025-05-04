<?php
namespace App\Models;

class OrdersModel extends Model
{

    public function getAllOrders()
    {
        return $this->getAll("orders");
    }
    public function getOrder($id)
    {
        return $this->getByID("orders", $id, "ID_Orders");
    }
    public function getFilteredOrders($search)
    {
        $search = "%$search%";
        $sql = "SELECT * FROM orders WHERE (name LIKE :search)";
        $params = [
            ':search' => $search,
        ];
        
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
        return $this->delete($id, "orders", "ID_Orders");
    }
}
?>