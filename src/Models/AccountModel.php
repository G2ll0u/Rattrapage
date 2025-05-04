<?php
namespace App\Models;

class AccountModel extends Model
{

    public function getAllAccounts()
    {
        return $this->getAll("user_");
    }
    public function getAccount($id)
    {
        return $this->getByID("user_", $id, "ID_User");
    }
    public function getAccountByRole($ID_Role)
    {
        return $this->getByFilter("user_", "ID_Role", $ID_Role);
    }
    public function getFilteredAccount($search)
    {
        $search = "%$search%";
        $sql = "SELECT * FROM user_ WHERE (first_name LIKE :search OR name LIKE :search OR email LIKE :search)";
        $params = [
            ':search' => $search,
        ];
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    public function getByEmail($email)
    {
        return $this->getByFilter("user_", "email", $email);
    }
    public function createAccount(string $first_name, string $last_name, string $email, string $password, int $role_id)
    {
        $data = [
            "first_name" => $first_name,
            "name" => $last_name,
            "email" => $email,
            "password" => password_hash($password, PASSWORD_DEFAULT),
            "ID_Role" => $role_id
        ];
        return $this->create("user_", $data);
    }
    public function updateAccount(int $id, string $first_name, string $last_name, string $email, string $password)
    {
        $current = $this->getAccount($id);   
        $data = [
            "first_name" => $first_name ?? $current->first_name,
            "name" => $last_name ?? $current->name,
            "email" => $email ?? $current->email,
            "password" => $password ? password_hash($password, PASSWORD_DEFAULT) : $current->password,
            "ID_Role" => $current->ID_Role
        ];
        return $this->update($id, "user_", $data, "ID_User");
    }
    public function deleteAccount(int $id)
    {
        return $this->delete($id, "user_", "ID_User");
    }
}
?>