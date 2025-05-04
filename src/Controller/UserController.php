<?php
namespace App\Controller;

use App\Models\ProductModel;
use App\Models\StockModel;
use App\Models\OrdersModel;
use App\Models\SupplierModel;
use App\Models\UserModel;
use Exception;

class UserController extends Controller {
    private $userModel;
    private $ProductModel;
    private $StockModel;
    private $OrderModel;
    private $SupplierModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->ProductModel = new ProductModel();
        //$this->StockModel = new StockModel();
        $this->OrderModel = new OrdersModel();
        //$this->SupplierModel = new SupplierModel();
    }

        /**
     * Nettoie et récupère une valeur du formulaire
     */
    private function getFormInput($key, $default = '') {
        return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
    }
    
    /**
     * Valide les champs requis d'un formulaire
     * @return bool|string True si valide, message d'erreur sinon
     */
    private function validateRequiredFields($fields) {
        foreach ($fields as $field) {
            if (empty($this->getFormInput($field))) {
                return "Tous les champs sont requis.";
            }
        }
        return true;
    }
    
    /**
     * Affiche une erreur et le formulaire
     */
    private function renderWithError($viewPath, $error) {
        $this->render($viewPath, ['error' => $error]);
        return;
    }

    /**
     * Page dashboard utilisateur (après connexion)
     */
    public function index() {
        // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
        if (!isset($_SESSION['user'])) {
            $this->redirect('home', 'index');
            return;
        }
        
        // Récupérer les statistiques et activités pour le dashboard
        try {
            $statsData = [
                'products' => $this->ProductModel->getAllProducts(),
                //'alerts' => $this->StockModel->countStockAlerts(),
                //'orders' => $this->OrderModel->countPendingOrders(),
                //'suppliers' => $this->SupplierModel->countActiveSuppliers()
            ];
            
           // $activities = $this->userModel->getRecentActivities(5);
            
            $this->render('user/home.twig', [
                'stats' => $statsData,
          //      'activities' => $activities
            ]);
        } catch (Exception $e) {
            // Même en cas d'erreur, afficher le dashboard avec des stats vides
            $this->render('user/home.twig', [
                'stats' => [],
                'activities' => [],
                'error' => 'Erreur lors du chargement des données: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Affiche la page de connexion et gère l'authentification
     */
    public function login() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect('home', 'index');
            return;
        }

        $email = $this->getFormInput("email");
        $password = $this->getFormInput("password", null);
        
        // Validation des champs
        $validation = $this->validateRequiredFields(['email', 'password']);
        if ($validation !== true) {
            return $this->renderWithError('home/index.twig', $validation);
        }

        $user = $this->userModel->getUserByEmail($email);

        // Vérification explicite si $user est un objet
        if (!$user || !is_object($user) || !password_verify($password, $user->password)) {
            return $this->renderWithError('home/index.twig', "Email ou mot de passe incorrect.");
        }

        // Gestion de l'option "Rester connecté"
        $remember = isset($_POST["remember"]) && $_POST["remember"] === "on";

        // Si tout est OK, on connecte l'utilisateur
        $_SESSION['user'] = [
            'ID_User' => $user->ID_User,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'email' => $user->email,
            'password' => $user->password,
            'role' => $user->role_name,
            'remember' => $remember
        ];

        // Gestion de l'option "Rester connecté"
        $_SESSION["remember"] = isset($_POST["remember"]) && $_POST["remember"] === "on";

        // Redirection vers le dashboard avec URL propre
        $this->redirect('user', 'index');
    }

/**
 * Déconnexion de l'utilisateur
 */
public function logout() {
    // Vérifier que la requête est en POST pour éviter les attaques CSRF
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Supprimer les données de session
        unset($_SESSION['user']);
        
        // Supprimer le cookie de connexion automatique si présent
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Détruire complètement la session
        session_destroy();
    }
    
    // Rediriger vers la page d'accueil après déconnexion
    $this->redirect('home', 'index');
}
    
    /**
     * Liste des utilisateurs (Admin uniquement)
     */
    public function list() {
        $this->checkAuth(['Admin']);
        
        $searchTerm = $_GET['search'] ?? '';
        
        try {
            if (!empty($searchTerm)) {
                $users = $this->userModel->searchUsers($searchTerm);
            } else {
                $users = $this->userModel->getAllUsers();
            }
            
            $this->render('user/list.twig', [
                'users' => $users,
                'searchTerm' => $searchTerm
            ]);
        } catch (Exception $e) {
            $this->render('user/list.twig', [
                'error' => 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage(),
                'users' => [],
                'searchTerm' => $searchTerm
            ]);
        }
    }
    
    /**
     * Affiche le formulaire de création d'utilisateur (Admin uniquement)
     */
    public function create() {
        $this->checkAuth(['Admin']);
        
        try {
            $roles = $this->userModel->getAllRoles();
            
            $this->render('user/create.twig', [
                'roles' => $roles
            ]);
        } catch (Exception $e) {
            $this->render('user/create.twig', [
                'error' => 'Erreur lors du chargement des rôles: ' . $e->getMessage(),
                'roles' => []
            ]);
        }
    }
    
    /**
     * Traitement du formulaire de création d'utilisateur (Admin uniquement)
     */
    public function store() {
        $this->checkAuth(['Admin']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('user', 'list');
            return;
        }
        
        $userData = [
            'name' => $_POST['name'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'ID_Role' => $_POST['ID_Role'] ?? ''
        ];
        
        // Validation des données
        $errors = $this->validateUserData($userData);
        
        if (!empty($errors)) {
            try {
                $roles = $this->userModel->getAllRoles();
                $this->render('user/create.twig', [
                    'errors' => $errors,
                    'userData' => $userData,
                    'roles' => $roles
                ]);
            } catch (Exception $e) {
                $this->render('user/create.twig', [
                    'error' => 'Erreur lors du chargement des rôles: ' . $e->getMessage(),
                    'errors' => $errors,
                    'userData' => $userData,
                    'roles' => []
                ]);
            }
            return;
        }
        
        try {
            $this->userModel->createUser($userData);
            
            // Message flash de succès
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Utilisateur créé avec succès!'
            ];
            
            // Redirection vers la liste des utilisateurs
            $this->redirect('user', 'list');
        } catch (Exception $e) {
            try {
                $roles = $this->userModel->getAllRoles();
                $this->render('user/create.twig', [
                    'error' => 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage(),
                    'userData' => $userData,
                    'roles' => $roles
                ]);
            } catch (Exception $innerE) {
                $this->render('user/create.twig', [
                    'error' => 'Erreur: ' . $e->getMessage() . ' / ' . $innerE->getMessage(),
                    'userData' => $userData,
                    'roles' => []
                ]);
            }
        }
    }
    
    /**
     * Affiche le formulaire de modification d'utilisateur (Admin ou l'utilisateur lui-même)
     */
    public function edit($id = null) {
        // Si aucun ID n'est fourni, utiliser l'ID de l'utilisateur connecté
        if ($id === null) {
            if (!isset($_SESSION['user'])) {
                $this->redirect('user', 'login');
                return;
            }
            $id = $_SESSION['user']['ID_User'];
        } else {
            // Vérifier que l'utilisateur est admin ou qu'il modifie son propre profil
            if ($_SESSION['user']['role'] !== 'Admin' && $_SESSION['user']['ID_User'] != $id) {
                $this->checkAuth(['Admin']);
            }
        }
        
        try {
            $user = $this->userModel->getUserById($id);
            
            if (!$user) {
                throw new Exception("Utilisateur introuvable.");
            }
            
            $roles = $this->userModel->getAllRoles();
            
            $this->render('user/edit.twig', [
                'user' => $user,
                'roles' => $roles,
                'isAdmin' => $_SESSION['user']['role'] === 'Admin',
                'isOwnProfile' => $_SESSION['user']['ID_User'] == $id
            ]);
        } catch (Exception $e) {
            // Message flash d'erreur
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ];
            
            // Redirection selon le contexte
            if ($_SESSION['user']['role'] === 'Admin') {
                $this->redirect('user', 'list');
            } else {
                $this->redirect('dashboard', 'index');
            }
        }
    }
    
    /**
     * Traitement du formulaire de modification d'utilisateur
     */
    public function update($id) {
        // Vérifier que l'utilisateur est admin ou qu'il modifie son propre profil
        if ($_SESSION['user']['role'] !== 'Admin' && $_SESSION['user']['ID_User'] != $id) {
            $this->checkAuth(['Admin']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('user', 'edit', ['id' => $id]);
            return;
        }
        
        // Récupérer les données du formulaire
        $userData = [
            'name' => $_POST['name'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'email' => $_POST['email'] ?? ''
        ];
        
        // Mot de passe (optionnel)
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }
        
        // Le rôle ne peut être modifié que par un admin
        if ($_SESSION['user']['role'] === 'Admin' && isset($_POST['ID_Role'])) {
            $userData['ID_Role'] = $_POST['ID_Role'];
        }
        
        // Validation des données
        $errors = $this->validateUserData($userData, true);
        
        if (!empty($errors)) {
            try {
                $user = $this->userModel->getUserById($id);
                $roles = $this->userModel->getAllRoles();
                
                $this->render('user/edit.twig', [
                    'errors' => $errors,
                    'user' => $user,
                    'userData' => $userData,
                    'roles' => $roles,
                    'isAdmin' => $_SESSION['user']['role'] === 'Admin',
                    'isOwnProfile' => $_SESSION['user']['ID_User'] == $id
                ]);
            } catch (Exception $e) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
                
                $this->redirect('user', 'edit', ['id' => $id]);
            }
            return;
        }
        
        try {
            $this->userModel->updateUser($id, $userData);
            
            // Si l'utilisateur modifie son propre profil, mettre à jour la session
            if ($_SESSION['user']['ID_User'] == $id) {
                $_SESSION['user']['name'] = $userData['name'];
                $_SESSION['user']['first_name'] = $userData['first_name'];
                $_SESSION['user']['email'] = $userData['email'];
                
                // Mettre à jour le rôle si modifié par un admin
                if (isset($userData['ID_Role'])) {
                    $updatedUser = $this->userModel->getUserById($id);
                    $_SESSION['user']['role'] = $updatedUser->role_name;
                }
            }
            
            // Message flash de succès
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Profil mis à jour avec succès!'
            ];
            
            // Redirection selon le contexte
            if ($_SESSION['user']['role'] === 'Admin' && $_SESSION['user']['ID_User'] != $id) {
                $this->redirect('user', 'list');
            } else {
                $this->redirect('dashboard', 'index');
            }
        } catch (Exception $e) {
            try {
                $user = $this->userModel->getUserById($id);
                $roles = $this->userModel->getAllRoles();
                
                $this->render('user/edit.twig', [
                    'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
                    'user' => $user,
                    'userData' => $userData,
                    'roles' => $roles,
                    'isAdmin' => $_SESSION['user']['role'] === 'Admin',
                    'isOwnProfile' => $_SESSION['user']['ID_User'] == $id
                ]);
            } catch (Exception $innerE) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
                
                $this->redirect('user', 'edit', ['id' => $id]);
            }
        }
    }
    
    /**
     * Suppression d'un utilisateur (Admin uniquement)
     */
    public function delete($id) {
        $this->checkAuth(['Admin']);
        
        // Un utilisateur ne peut pas se supprimer lui-même
        if ($_SESSION['user']['ID_User'] == $id) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ];
            
            $this->redirect('user', 'list');
            return;
        }
        
        try {
            $this->userModel->deleteUser($id);
            
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Utilisateur supprimé avec succès!'
            ];
        } catch (Exception $e) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ];
        }
        
        $this->redirect('user', 'list');
    }
    
    /**
     * Affiche le profil de l'utilisateur connecté
     */
    public function profile() {
        if (!isset($_SESSION['user'])) {
            $this->redirect('user', 'login');
            return;
        }
        
        $id = $_SESSION['user']['ID_User'];
        
        try {
            $user = $this->userModel->getUserById($id);
            $authHistory = $this->userModel->getUserAuthHistory($id);
            
            $this->render('user/profile.twig', [
                'user' => $user,
                'authHistory' => $authHistory
            ]);
        } catch (Exception $e) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Erreur lors du chargement du profil: ' . $e->getMessage()
            ];
            
            $this->redirect('dashboard', 'index');
        }
    }
    
    /**
     * Récupère les notifications pour l'application Java (API)
     */
    public function getNotifications() {
        // Vérifier l'authentification via API key ou token
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($apiKey) && empty($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentification requise']);
            return;
        }
        
        try {
            // Pour simplifier, utilisons l'ID 1 comme exemple (à remplacer par l'authentification réelle)
            $userId = 1;
            
            $notifications = $this->userModel->prepareJavaNotifications($userId);
            
            // Envoyer les notifications au format JSON
            header('Content-Type: application/json');
            echo json_encode($notifications);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Affichage du formulaire de réinitialisation de mot de passe
     */
    public function resetPassword() {
        // Si l'utilisateur est déjà connecté, le rediriger vers le dashboard
        if (isset($_SESSION['user'])) {
            $this->redirect('dashboard', 'index');
            return;
        }
        
        $this->render('user/reset-password.twig');
    }
    
  /**
 * Traitement de la demande de réinitialisation de mot de passe
 * Version simplifiée qui renvoie directement à la page de contact administrateur
 */
public function requestPasswordReset() {
    // Si l'utilisateur est déjà connecté, le rediriger vers le dashboard
    if (isset($_SESSION['user'])) {
        $this->redirect('user', 'index');
        return;
    }
    
    // Affiche simplement la page avec le lien de contact
    $this->render('user/reset-password.twig', [
        'currentTime' => date('Y-m-d H:i:s')
    ]);
}
    
    /**
     * Validation des données utilisateur
     */
    private function validateUserData($data, $isUpdate = false) {
        $errors = [];
        
        // Validation du nom
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est requis.';
        } elseif (strlen($data['name']) > 50) {
            $errors['name'] = 'Le nom ne peut pas dépasser 50 caractères.';
        }
        
        // Validation du prénom
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'Le prénom est requis.';
        } elseif (strlen($data['first_name']) > 50) {
            $errors['first_name'] = 'Le prénom ne peut pas dépasser 50 caractères.';
        }
        
        // Validation de l'email
        if (empty($data['email'])) {
            $errors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide.';
        } elseif (strlen($data['email']) > 50) {
            $errors['email'] = 'L\'email ne peut pas dépasser 50 caractères.';
        }
        
        // Validation du mot de passe (uniquement si fourni ou en création)
        if (!$isUpdate || isset($data['password'])) {
            if (empty($data['password']) && !$isUpdate) {
                $errors['password'] = 'Le mot de passe est requis.';
            } elseif (!empty($data['password']) && strlen($data['password']) < 8) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
            }
        }
        
        // Validation du rôle (si fourni)
        if (isset($data['ID_Role'])) {
            if (empty($data['ID_Role'])) {
                $errors['ID_Role'] = 'Le rôle est requis.';
            } elseif (!is_numeric($data['ID_Role'])) {
                $errors['ID_Role'] = 'Format de rôle invalide.';
            }
        }
        
        return $errors;
    }
    /**
     * Vérifie si l'utilisateur est administrateur
     * @return bool True si l'utilisateur est admin, false sinon
     */
    private function isAdmin() {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin';
    }

    /**
     * Vérifie si l'utilisateur est manager ou administrateur
     * @return bool True si l'utilisateur est manager ou admin, false sinon
     */
    private function isManager() {
        return isset($_SESSION['user']['role']) && 
            ($_SESSION['user']['role'] === 'Manager' || $_SESSION['user']['role'] === 'Admin');
    }
}
?>