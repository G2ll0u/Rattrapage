<?php
namespace App\Models;

use PDO;
use Exception;

class UserModel extends Model {
    /**
     * Table correspondante dans la base de données
     */
    protected $table = 'user_';
    protected $idField = 'ID_User';
    
    /**
     * Récupère tous les utilisateurs avec leur rôle
     */
    public function getAllUsers() {
        try {
            $sql = "SELECT u.*, r.name AS role_name 
                    FROM user_ u 
                    JOIN role r ON u.ID_Role = r.ID_Role 
                    ORDER BY u.name, u.first_name";
            $stmt = $this->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère un utilisateur par son ID
     */
    public function getUserById($id) {
        try {
            $sql = "SELECT u.*, r.name AS role_name 
                    FROM user_ u 
                    JOIN role r ON u.ID_Role = r.ID_Role 
                    WHERE u.ID_User = ?";
            $stmt = $this->query($sql, [$id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère un utilisateur par son email
     */
    public function getUserByEmail($email) {
        $sql = "SELECT u.*, r.name AS role_name 
                FROM user_ u 
                JOIN role r ON u.ID_Role = r.ID_Role 
                WHERE u.email = ?";
        $stmt = $this->query($sql, [$email]);
        return $stmt->fetch(PDO::FETCH_OBJ); 
    }
    
    /**
     * Recherche d'utilisateurs par nom, prénom ou email
     */
    public function searchUsers($term) {
        try {
            $sql = "SELECT u.*, r.name AS role_name 
                    FROM user_ u 
                    JOIN role r ON u.ID_Role = r.ID_Role 
                    WHERE u.name LIKE :term 
                    OR u.first_name LIKE :term 
                    OR u.email LIKE :term 
                    ORDER BY u.name, u.first_name";
            $stmt = $this->query($sql, ['term' => "%$term%"]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la recherche d'utilisateurs: " . $e->getMessage());
        }
    }
    
    /**
     * Crée un nouvel utilisateur
     */
    public function createUser($userData) {
        try {
            // Vérification que l'email n'existe pas déjà
            $emailCheck = $this->getUserByEmail($userData['email']);
            if ($emailCheck) {
                throw new Exception("Un utilisateur avec cet email existe déjà.");
            }
            
            // Hashage du mot de passe pour sécurité
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Enregistrement de l'audit
            $this->logAction('CREATE', $userData);
            
            // Création de l'utilisateur
            return $this->create($this->table, $userData);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la création de l'utilisateur: " . $e->getMessage());
        }
    }
    
    /**
     * Met à jour un utilisateur existant
     */
    public function updateUser($id, $userData) {
        try {
            // Vérifier si l'email est modifié et s'il est déjà utilisé
            if (isset($userData['email'])) {
                $existingUser = $this->getUserByEmail($userData['email']);
                if ($existingUser && $existingUser->ID_User != $id) {
                    throw new Exception("Un utilisateur avec cet email existe déjà.");
                }
            }
            
            // Si le mot de passe est fourni, le hasher
            if (isset($userData['password']) && !empty($userData['password'])) {
                $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            } else {
                // Si le mot de passe est vide, le supprimer du tableau pour ne pas l'écraser
                unset($userData['password']);
            }
            
            // Enregistrement de l'audit
            $this->logAction('UPDATE', $userData, $id);
            
            // Mise à jour de l'utilisateur
            return $this->update($id, $this->table, $userData, $this->idField);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
        }
    }
    
    /**
     * Supprime un utilisateur
     */
    public function deleteUser($id) {
        try {
            // Vérifier que l'utilisateur existe
            $user = $this->getUserById($id);
            if (!$user) {
                throw new Exception("Utilisateur introuvable.");
            }
            
            // Vérifier qu'il n'y a pas de contraintes (par exemple, des mouvements de stock associés)
            $sql = "SELECT COUNT(*) as count FROM stock_movement WHERE ID_User = ?";
            $stmt = $this->query($sql, [$id]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            
            if ($result->count > 0) {
                throw new Exception("Impossible de supprimer cet utilisateur car il est associé à des mouvements de stock.");
            }
            
            // Enregistrement de l'audit
            $this->logAction('DELETE', [], $id);
            
            // Suppression de l'utilisateur
            return $this->delete($id, $this->table, $this->idField);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la suppression de l'utilisateur: " . $e->getMessage());
        }
    }
    
/**
 * Authentifie un utilisateur
 */
public function authenticateUser($email, $password) {
    try {
        $sql = "SELECT u.*, r.name AS role_name 
                FROM user_ u 
                JOIN role r ON u.ID_Role = r.ID_Role 
                WHERE u.email = ?";
        $stmt = $this->query($sql, [$email]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$user) {
            return false; // Email non trouvé
        }
        
        if (password_verify($password, $user->password)) {
            // Mise à jour des informations de log pour la sécurité
            $this->logAuthentication($user->ID_User, true);
            return $user;
        }
        
        // Mot de passe incorrect
        $this->logAuthentication($user->ID_User, false);
        return false;
    } catch (Exception $e) {
        error_log("Erreur d'authentification: " . $e->getMessage());
        throw new Exception("Erreur lors de l'authentification: " . $e->getMessage());
    }
}
    
    /**
     * Récupère tous les rôles disponibles
     */
    public function getAllRoles() {
        try {
            $sql = "SELECT * FROM role ORDER BY ID_Role";
            $stmt = $this->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des rôles: " . $e->getMessage());
        }
    }
    
    /**
     * Enregistre les tentatives de connexion dans l'audit log
     */
    private function logAuthentication($userId, $success) {
        try {
            $action = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
            $sql = "INSERT INTO audit_log (ID_User, action, table_name, log_timestamp) 
                    VALUES (?, ?, 'user', NOW())";
            $this->query($sql, [$userId, $action]);
        } catch (Exception $e) {
            // Ne pas bloquer l'application si l'enregistrement du log échoue
            error_log("Erreur d'enregistrement de l'audit: " . $e->getMessage());
        }
    }
    
    /**
     * Enregistre les actions CRUD dans l'audit log
     */
    private function logAction($action, $data = [], $userId = null) {
        try {
            // Récupérer l'ID de l'utilisateur connecté
            $currentUserId = isset($_SESSION['user']) ? $_SESSION['user']['ID_User'] : null;
            
            $sql = "INSERT INTO audit_log (ID_User, action, table_name, row_id, log_timestamp) 
                    VALUES (?, ?, 'user', ?, NOW())";
            $this->query($sql, [$currentUserId, $action, $userId]);
        } catch (Exception $e) {
            // Ne pas bloquer l'application si l'enregistrement du log échoue
            error_log("Erreur d'enregistrement de l'audit: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère l'historique des connexions d'un utilisateur
     */
    public function getUserAuthHistory($userId, $limit = 10) {
        try {
            $sql = "SELECT * FROM audit_log 
                    WHERE ID_User = ? 
                    AND (action = 'LOGIN_SUCCESS' OR action = 'LOGIN_FAILED') 
                    ORDER BY log_timestamp DESC 
                    LIMIT ?";
            $stmt = $this->query($sql, [$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération de l'historique: " . $e->getMessage());
        }
    }
    
    /**
     * Prépare les notifications pour l'application Java
     */
    public function prepareJavaNotifications($userId) {
        try {
            // Récupération des alertes de stock pour notifications
            $sql = "SELECT p.ID_Product, p.name, p.reference, p.amount, p.alert_threshold 
                    FROM product p 
                    WHERE p.amount <= p.alert_threshold 
                    ORDER BY (p.amount = 0) DESC, p.amount ASC";
            $stmt = $this->query($sql);
            $alerts = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            // Récupération des commandes en attente
            $sql = "SELECT o.ID_Orders, o.expected_date, p.name AS provider 
                    FROM orders o 
                    JOIN provider p ON o.ID_Provider = p.ID_Provider 
                    WHERE o.status = 'pending' 
                    AND o.expected_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)";
            $stmt = $this->query($sql);
            $pendingOrders = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            return [
                'stock_alerts' => $alerts,
                'pending_orders' => $pendingOrders,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la préparation des notifications: " . $e->getMessage());
        }
    }
}
?>