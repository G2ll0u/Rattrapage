<?php
namespace App\Models;

class SupplierModel extends Model
{
    /**
     * Récupère tous les fournisseurs
     */
    public function getAllSuppliers()
    {
        return $this->getAll("provider");
    }
    
    /**
     * Récupère un fournisseur par son ID
     */
    public function getSupplier($id)
    {
        return $this->getByID("provider", $id, "ID_Provider");
    }
    
    /**
     * Récupère les fournisseurs filtrés par un terme de recherche
     */
    public function getFilteredSuppliers($search)
    {
        $search = "%$search%";
        $sql = "SELECT * FROM provider WHERE 
                name LIKE :search OR 
                contact_name LIKE :search OR 
                email LIKE :search";
        $params = [
            ':search' => $search,
        ];
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    /**
     * Crée un nouveau fournisseur
     */
    public function createSupplier(string $name, string $email, string $phone, string $address, string $contact_name)
    {
        $data = [
            "name" => $name,
            "email" => $email,
            "phone" => $phone,
            "address" => $address,
            "contact_name" => $contact_name
        ];
        return $this->create("provider", $data);
    }
    
    /**
     * Met à jour un fournisseur existant
     */
    public function updateSupplier(int $id, string $name, string $email, string $phone, string $address, string $contact_name)
    {
        $current = $this->getSupplier($id);   
        $data = [
            "name" => $name ?? $current->name,
            "email" => $email ?? $current->email,
            "phone" => $phone ?? $current->phone,
            "address" => $address ?? $current->address,
            "contact_name" => $contact_name ?? $current->contact_name
        ];
        return $this->update($id, "provider", $data, "ID_Provider");
    }
    
    /**
     * Supprime un fournisseur
     */
    public function deleteSupplier(int $id)
    {
        return $this->delete($id, "provider", "ID_Provider");
    }
    
    /**
     * Compte les fournisseurs actifs
     */
    public function countActiveSuppliers()
    {
        $sql = "SELECT COUNT(*) as count FROM provider";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        return $result->count;
    }
}
?>