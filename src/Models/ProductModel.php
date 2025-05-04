<?php
namespace App\Models;

class ProductModel extends Model
{

    public function getAllProducts()
    {
        return $this->getAll("product");
    }
    public function getProduct($id)
    {
        return $this->getByID("product", $id, "ID_Product");
    }
    public function getProductByCategory($ID_Category)
    {
        return $this->getByFilter("product", "ID_Product", $ID_Category);
    }
    public function getFilteredProduct($search)
    {
        $search = "%$search%";
        $sql = "SELECT * FROM product WHERE (name LIKE :search)";
        $params = [
            ':search' => $search,
        ];
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function createProduct(string $reference, string $name, int $amount, float $price, $last_delivery, int $alert_threshold, int $ID_Category)
    {
        $data = [
            "reference" => $reference,
            "name" => $name,
            "amount" => $amount,
            "price" => $price,
            "last_delivery" => $last_delivery,
            "alert_threshold" => $alert_threshold,
            "ID_Category" => $ID_Category
        ];
        return $this->create("products", $data);
    }
    public function updateProduct(int $id, string $reference, string $name, int $amount, float $price, $last_delivery, int $alert_threshold, int $ID_Category)
    {
        $current = $this->getProduct($id);   
        $data = [
            "reference" => $reference ?? $current->reference,
            "name" => $name ?? $current->name,
            "amount" => $amount ?? $current->amount,
            "price" => $price ?? $current->price,
            "last_delivery" => $last_delivery ?? $current->last_delivery,
            "alert_threshold" => $alert_threshold ?? $current->alert_threshold,
            "ID_Category" => $ID_Category ?? $current->ID_Category
        ];
        return $this->update($id, "product", $data, "ID_Product");
    }
    public function deleteProduct(int $id)
    {
        return $this->delete($id, "product", "ID_Product");
    }
}
?>