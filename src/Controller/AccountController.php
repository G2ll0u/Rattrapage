<?php
namespace App\Controller;

use App\Models\UserModel;
use App\Models\RoleModel;
use Exception;
use PDO;

class AccountController extends Controller
{
    private $userModel;
    private $roleModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }
    
    /**
     * Affichage de la liste des utilisateurs
     */
    public function index()
    {
        // Vérification des droits d'accès
        $this->checkAuth(['Admin']);
        
        // Paramètres de filtrage et tri
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $sort = $_GET['sort'] ?? 'name';
        $direction = $_GET['direction'] ?? 'asc';
        
        // Récupération et préparation des données
        $users = $this->getFilteredSortedUsers($search, $role, $sort, $direction);
        $roles = $this->roleModel->getAllRoles();
        $roleNames = $this->getRoleNames($roles);
        
        // Rendu de la vue
        $this->render('account/index.twig', [
            'users' => $users,
            'roles' => $roles,
            'roleNames' => $roleNames,
            'search' => $search,
            'selectedRole' => $role,
            'sort' => $sort,
            'direction' => $direction,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Gestion des utilisateurs'
        ]);
    }
    
    /**
     * Gestion complète des utilisateurs (CRUD)
     */
    public function manage()
    {
        $this->checkAuth(['Admin']);
        
        $segments = explode('/', $_GET['uri'] ?? '');
        $pathId = isset($segments[2]) ? intval($segments[2]) : null;
        
        // Récupération des paramètres
        $action = $_GET['action'] ?? 'list';
        $id = $_GET['id'] ?? $pathId;
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $sort = $_GET['sort'] ?? 'name';
        $direction = $_GET['direction'] ?? 'asc';
        
        // Initialisation des variables
        $notification = $this->initNotification();
        $currentUser = null;
        $errors = [];
        $success = '';
        
        // Récupération des données de base
        $roles = $this->roleModel->getAllRoles();
        $users = $this->getFilteredSortedUsers($search, $role, $sort, $direction);
        $roleNames = $this->getRoleNames($roles);
        
        // Traitement des actions CRUD
        if (in_array($action, ['edit', 'view', 'delete']) && $id) {
            $currentUser = $this->userModel->getUserById($id);
            if (!$currentUser) {
                $action = 'list';
                $notification = [
                    'type' => 'danger',
                    'title' => 'Erreur',
                    'message' => 'Utilisateur introuvable',
                    'show' => true
                ];
            }
        }
        
        // Traitement des formulaires soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            [$action, $success, $errors, $notification, $currentUser, $users] = 
                $this->handleFormSubmission(
                    $_POST['action'], 
                    $search, 
                    $role, 
                    $sort, 
                    $direction
                );
        }
        
        // Notification pour la recherche d'utilisateurs
        $notification = $this->getSearchNotification($search, $role, $users, $notification);
        
        // Rendu de la vue
        $this->render('account/manage.twig', [
            'action' => $action,
            'users' => $users,
            'roles' => $roles,
            'roleNames' => $roleNames,
            'currentUser' => $currentUser,
            'search' => $search,
            'selectedRole' => $role,
            'sort' => $sort,
            'direction' => $direction,
            'errors' => $errors,
            'success' => $success,
            'notification' => $notification,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => $this->getPageTitle($action)
        ]);
    }
    
    /**
     * Affiche la page de création d'utilisateur
     */
    public function create()
    {
        $this->checkAuth(['Admin']);
        
        $roles = $this->roleModel->getAllRoles();
        
        $this->render('account/create.twig', [
            'roles' => $roles,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Ajouter un nouvel utilisateur'
        ]);
    }
    
    /**
     * Affiche la page détaillée d'un utilisateur
     */
    public function view()
    {
        $this->checkAuth(['Admin']);
        
        $segments = explode('/', $_GET['uri'] ?? '');
        $pathId = isset($segments[2]) ? intval($segments[2]) : null;
        
        $id = $_GET['id'] ?? $pathId;
        
        if (!$id) {
            $this->redirect('account', 'index');
            return;
        }
        
        $user = $this->userModel->getUserById($id);
        if (!$user) {
            $this->redirect('account', 'index');
            return;
        }
        
        $role = $this->roleModel->getRole($user->ID_Role);
        
        $this->render('account/view.twig', [
            'user_' => $user,
            'role' => $role,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Détails de l\'utilisateur: ' . $user->first_name . ' ' . $user->name
        ]);
    }
    
    /**
     * Traite la création d'un nouvel utilisateur
     * @return array [message de succès, erreurs]
     */
    private function handleCreate()
    {
        if (!$this->isAdmin()) {
            return ["", ["Vous n'avez pas les permissions nécessaires"]];
        }
        
        $errors = [];
        
        // Validation des données
        $first_name = trim($_POST['first_name'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ID_Role = intval($_POST['ID_Role'] ?? 0);
        
        // Vérifications basiques
        if (empty($first_name)) {
            $errors[] = "Le prénom est obligatoire";
        }
        if (empty($name)) {
            $errors[] = "Le nom est obligatoire";
        }
        if (empty($email)) {
            $errors[] = "L'email est obligatoire";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide";
        }
        if (empty($password)) {
            $errors[] = "Le mot de passe est obligatoire";
        } elseif (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        if ($ID_Role <= 0) {
            $errors[] = "Veuillez sélectionner un rôle";
        }
        
        // Si aucune erreur, créer l'utilisateur
        if (empty($errors)) {
            try {
                // Vérifier si l'email existe déjà
                $existingUser = $this->userModel->getUserByEmail($email);
                if ($existingUser) {
                    return ["", ["Cet email est déjà utilisé"]];
                }
                
                $userData = [
                    'first_name' => $first_name,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'ID_Role' => $ID_Role
                ];
                
                $success = $this->userModel->createUser($userData);
                
                if ($success) {
                    return ["Utilisateur \"$first_name $name\" ajouté avec succès", []];
                } else {
                    return ["", ["Erreur lors de l'ajout de l'utilisateur"]];
                }
            } catch (Exception $e) {
                return ["", ["Erreur: " . $e->getMessage()]];
            }
        }
        
        return ["", $errors];
    }
    
    /**
     * Traite la mise à jour d'un utilisateur
     * @return array [message de succès, erreurs, utilisateur mis à jour]
     */
    private function handleUpdate()
    {
        if (!$this->isAdmin()) {
            return ["", ["Vous n'avez pas les permissions nécessaires"], null];
        }
        
        $errors = [];
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            return ["", ["ID de l'utilisateur manquant"], null];
        }
        
        try {
            // Récupération de l'utilisateur existant
            $user = $this->userModel->getUserById($id);
            if (!$user) {
                return ["", ["Utilisateur introuvable"], null];
            }
            
            // Validation des données
            $first_name = trim($_POST['first_name'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $ID_Role = intval($_POST['ID_Role'] ?? 0);
            
            // Vérifications basiques
            if (empty($first_name)) {
                $errors[] = "Le prénom est obligatoire";
            }
            if (empty($name)) {
                $errors[] = "Le nom est obligatoire";
            }
            if (empty($email)) {
                $errors[] = "L'email est obligatoire";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format d'email invalide";
            }
            if (!empty($password) && strlen($password) < 8) {
                $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
            }
            if ($ID_Role <= 0) {
                $errors[] = "Veuillez sélectionner un rôle";
            }
            
            // Si aucune erreur, mettre à jour l'utilisateur
            if (empty($errors)) {
                // Vérifier si l'email existe déjà (pour un autre utilisateur)
                $existingUser = $this->userModel->getUserByEmail($email);
                if ($existingUser && $existingUser->ID_User != $id) {
                    return ["", ["Cet email est déjà utilisé par un autre compte"], $user];
                }
                
                $userData = [
                    'first_name' => $first_name,
                    'name' => $name,
                    'email' => $email,
                    'ID_Role' => $ID_Role
                ];
                
                // Ajouter le mot de passe uniquement s'il est fourni
                if (!empty($password)) {
                    $userData['password'] = $password;
                }
                
                $success = $this->userModel->updateUser($id, $userData);
                
                if ($success) {
                    $updatedUser = $this->userModel->getUserById($id);
                    
                    // Si l'utilisateur modifie son propre profil, mettre à jour la session
                    if (isset($_SESSION['user']['ID_User']) && $_SESSION['user']['ID_User'] == $id) {
                        $_SESSION['user']['name'] = $name;
                        $_SESSION['user']['first_name'] = $first_name;
                        $_SESSION['user']['email'] = $email;
                        $_SESSION['user']['role'] = $updatedUser->role_name;
                    }
                    
                    return ["Utilisateur \"$first_name $name\" mis à jour avec succès", [], $updatedUser];
                } else {
                    return ["", ["Erreur lors de la mise à jour de l'utilisateur"], $user];
                }
            }
            
            return ["", $errors, $user];
            
        } catch (Exception $e) {
            return ["", ["Erreur: " . $e->getMessage()], null];
        }
    }
    
    /**
     * Traite la suppression d'un utilisateur
     * @return array [message de succès, erreurs]
     */
    private function handleDelete()
    {
        if (!$this->isAdmin()) {
            return ["", ["Vous n'avez pas les permissions nécessaires"]];
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            return ["", ["ID de l'utilisateur manquant"]];
        }
        
        try {
            $user = $this->userModel->getUserById($id);
            if (!$user) {
                return ["", ["Utilisateur introuvable"]];
            }
            
            // Empêcher la suppression de son propre compte
            if (isset($_SESSION['user']['ID_User']) && $_SESSION['user']['ID_User'] == $id) {
                return ["", ["Vous ne pouvez pas supprimer votre propre compte"]];
            }
            
            $name = $user->first_name . ' ' . $user->name;
            $success = $this->userModel->deleteUser($id);
            
            if ($success) {
                return ["Utilisateur \"$name\" supprimé avec succès", []];
            } else {
                return ["", ["Erreur lors de la suppression de l'utilisateur"]];
            }
        } catch (Exception $e) {
            return ["", ["Erreur: " . $e->getMessage()]];
        }
    }
    
    /**
     * Recherche d'un utilisateur
     */
    public function search()
    {
        $this->checkAuth(['Admin']);
        
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $search = trim($_POST["search_query"] ?? '');
            $role = $_POST["role"] ?? '';
            
            $this->redirect('account', 'index', [
                'search' => $search,
                'role' => $role
            ]);
        }
    }
    
    /**
     * Traite la soumission du formulaire et renvoie les données mises à jour
     */
    private function handleFormSubmission($action, $search, $role, $sort, $direction)
    {
        $errors = [];
        $success = '';
        $notification = $this->initNotification();
        $currentUser = null;
        
        switch ($action) {
            case 'create':
                [$success, $errors] = $this->handleCreate();
                if (empty($errors)) {
                    $notification = [
                        'type' => 'success',
                        'title' => 'Succès',
                        'message' => $success,
                        'show' => true
                    ];
                    $action = 'list';
                } else {
                    $notification = [
                        'type' => 'danger',
                        'title' => 'Erreur',
                        'message' => implode(', ', $errors),
                        'show' => true
                    ];
                }
                break;
                
            case 'update':
                [$success, $errors, $currentUser] = $this->handleUpdate();
                if (empty($errors)) {
                    $notification = [
                        'type' => 'success',
                        'title' => 'Succès',
                        'message' => $success,
                        'show' => true
                    ];
                    $action = 'list';
                } else {
                    $notification = [
                        'type' => 'danger',
                        'title' => 'Erreur',
                        'message' => implode(', ', $errors),
                        'show' => true
                    ];
                }
                break;
                
            case 'delete':
                [$success, $errors] = $this->handleDelete();
                if (empty($errors)) {
                    $notification = [
                        'type' => 'success',
                        'title' => 'Succès',
                        'message' => $success,
                        'show' => true
                    ];
                } else {
                    $notification = [
                        'type' => 'danger',
                        'title' => 'Erreur',
                        'message' => implode(', ', $errors),
                        'show' => true
                    ];
                }
                $action = 'list';
                break;
        }
        
        // Actualiser la liste des utilisateurs
        $users = empty($errors) ? 
            $this->getFilteredSortedUsers($search, $role, $sort, $direction) : 
            $this->getFilteredUsers($search, $role);
        
        return [$action, $success, $errors, $notification, $currentUser, $users];
    }
    
    /**
     * Récupère les utilisateurs filtrés selon les critères
     */
    private function getFilteredUsers($search, $role)
    {
        if (!empty($search)) {
            $users = $this->userModel->searchUsers($search);
        } else {
            $users = $this->userModel->getAllUsers();
        }
        
        if (!empty($role)) {
            $users = array_filter($users, function($user) use ($role) {
                return $user->ID_Role == $role;
            });
        }
        
        return $users;
    }
    
    /**
     * Récupère les utilisateurs filtrés et triés selon les critères
     */
    private function getFilteredSortedUsers($search, $role, $sort, $direction)
    {
        $users = $this->getFilteredUsers($search, $role);
        return $this->sortUsers($users, $sort, $direction);
    }
    
    /**
     * Trie les utilisateurs selon les critères
     */
    private function sortUsers($users, $sort, $direction)
    {
        usort($users, function($a, $b) use ($sort, $direction) {
            if ($direction === 'asc') {
                return $a->$sort <=> $b->$sort;
            } else {
                return $b->$sort <=> $a->$sort;
            }
        });
        
        return $users;
    }
    
    /**
     * Vérifie si l'utilisateur est administrateur
     */
    private function isAdmin()
    {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin';
    }
    
    /**
     * Vérifie si l'utilisateur est manager ou administrateur
     */
    private function isManager()
    {
        return isset($_SESSION['user']['role']) && 
              ($_SESSION['user']['role'] === 'Manager' || $_SESSION['user']['role'] === 'Admin');
    }
    
    /**
     * Initialise un objet de notification vide
     */
    private function initNotification()
    {
        return [
            'type' => '',
            'title' => '',
            'message' => '',
            'show' => false
        ];
    }
    
    /**
     * Retourne le titre de la page selon l'action courante
     */
    private function getPageTitle($action)
    {
        switch ($action) {
            case 'create': return 'Ajouter un utilisateur';
            case 'edit': return 'Modifier un utilisateur';
            case 'view': return 'Détails de l\'utilisateur';
            case 'delete': return 'Supprimer un utilisateur';
            default: return 'Gestion des utilisateurs';
        }
    }
    
    /**
     * Prépare un dictionnaire de noms de rôles indexé par ID
     */
    private function getRoleNames($roles)
    {
        $roleNames = [];
        foreach ($roles as $role) {
            $roleNames[$role->ID_Role] = $role->name;
        }
        return $roleNames;
    }
    
    /**
     * Génère une notification pour la recherche d'utilisateurs
     */
    private function getSearchNotification($search, $role, $users, $notification)
    {
        if (($search || $role) && $notification['type'] === '') {
            $count = count($users);
            if ($count > 0) {
                return [
                    'type' => 'info',
                    'title' => 'Recherche',
                    'message' => "Votre recherche a retourné $count utilisateur(s)",
                    'show' => true
                ];
            } else {
                return [
                    'type' => 'warning',
                    'title' => 'Recherche',
                    'message' => "Aucun utilisateur trouvé pour votre recherche",
                    'show' => true
                ];
            }
        }
        return $notification;
    }
}