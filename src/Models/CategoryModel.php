<?php
namespace App\Models;

class CategoryModel extends Model
{

    public function getAllCategories()
    {
        return $this->getAll("category");
    }
    public function getCategory($id)
    {
        return $this->getByID("category", $id, "ID_Category");
    }
    public function getFilteredCategory($search)
    {
        $search = "%$search%";
        $sql = "SELECT * FROM category WHERE (name LIKE :search)";
        $params = [
            ':search' => $search,
        ];
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function createCategory(string $name)
    {
        $data = [
            "name" => $name,
        ];
        return $this->create("category", $data);
    }
    public function updateCategory(int $id, string $name)
    {
        $current = $this->getCategory($id);   
        $data = [
            "name" => $name ?? $current->name,
        ];
        return $this->update($id, "category", $data, "ID_Category");
    }
    public function deleteCategory(int $id)
    {
        return $this->delete($id, "category", "ID_Category");
    }
}
?>