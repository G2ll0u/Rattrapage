<?php
namespace App\Models;

class DeliveryModel extends Model
{

    public function getAllDeliveries()
    {
        return $this->getAll("delivery");
    }
    public function getDelivery($id)
    {
        return $this->getByID("delivery", $id, "ID_Delivery");
    }
    public function getFilteredDelivery($search)
    {
        $search = "%$search%";
        $sql = "SELECT * FROM delivery WHERE (name LIKE :search)";
        $params = [
            ':search' => $search,
        ];
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function createDelivery($delivery_date, $qty, $unit_cost, $ID_Product, $ID_Provider)
    {
        $data = [
            "delivery_date" => $delivery_date,
            "qty" => $qty,
            "unit_cost" => $unit_cost,
            "ID_Product" => $ID_Product,
            "ID_Provider" => $ID_Provider
        ];
        return $this->create("delivery", $data);
    }
    public function updateDelivery(int $id, $delivery_date, $qty, $unit_cost, $ID_Product, $ID_Provider)
    {
        $current = $this->getDelivery($id);   
        $data = [
            "delivery_date" => $delivery_date ?? $current->delivery_date,
            "qty" => $qty ?? $current->qty,
            "unit_cost" => $unit_cost ?? $current->unit_cost,
            "ID_Product" => $ID_Product ?? $current->ID_Product,
            "ID_Provider" => $ID_Provider ?? $current->ID_Provider
        ];
        return $this->update($id, "delivery", $data, "ID_Delivery");
    }
    public function deleteDelivery(int $id)
    {
        return $this->delete($id, "delivery", "ID_Delivery");
    }
}
?>