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
        return $this->getById("category", $id, "ID_Category");
    }
    
    public function createCategory($name)
    {
        $data = ["name" => $name];
        return $this->create("category", $data);
    }
    
    public function updateCategory($id, $name)
    {
        $data = ["name" => $name];
        return $this->update($id, "category", $data, "ID_Category");
    }
    
    public function deleteCategory($id)
    {
        return $this->delete($id, "category", "ID_Category");
    }
}