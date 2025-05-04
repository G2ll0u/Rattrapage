<?php
namespace App\Models;

class RoleModel extends Model
{
    public function getAllRoles()
    {
        return $this->getAll("role");
    }
    
    public function getRole($id)
    {
        return $this->getById("role", $id, "ID_Role");
    }
}