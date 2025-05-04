<?php
namespace App\Controller;

use App\Models\SupplierModel;

class SupplierController extends Controller {
    private $supplierModel;
    
    public function __construct() {
        parent::__construct();
        $this->supplierModel = new SupplierModel();
    }
    
    /**
     * Page d'accueil des fournisseurs - Affichage simple
     */
    public function index() {
        $this->checkAuth();
        
        // Paramètres de filtrage et tri
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'name';
        $direction = $_GET['direction'] ?? 'asc';
        
        // Récupération et préparation des données
        $suppliers = $this->getFilteredSortedSuppliers($search, $sort, $direction);
        
        // Rendu de la vue
        $this->render('provider/index.twig', [
            'suppliers' => $suppliers,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Liste des fournisseurs'
        ]);
    }
    
    /**
     * Gestion complète des fournisseurs (CRUD)
     */
    public function manage() {
        $this->checkAuth();
        
        $segments = explode('/', $_GET['uri'] ?? '');
        $pathId = isset($segments[2]) ? intval($segments[2]) : null;

        // Récupération des paramètres
        $action = $_GET['action'] ?? 'list';
        $id = $_GET['id'] ?? $pathId;
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'name';
        $direction = $_GET['direction'] ?? 'asc';

        // Initialisation des variables
        $notification = $this->initNotification();
        $currentSupplier = null;
        $errors = [];
        $success = '';
        
        // Récupération des données de base
        $suppliers = $this->getFilteredSortedSuppliers($search, $sort, $direction);
        
        // Traitement des actions CRUD
        if (in_array($action, ['edit', 'view', 'delete']) && $id) {
            $currentSupplier = $this->supplierModel->getSupplier($id);
            if (!$currentSupplier) {
                $action = 'list';
                $notification = [
                    'type' => 'danger',
                    'title' => 'Erreur',
                    'message' => 'Fournisseur introuvable',
                    'show' => true
                ];
            }
        }
        
        // Traitement des formulaires soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            [$action, $success, $errors, $notification, $currentSupplier, $suppliers] = 
                $this->handleFormSubmission(
                    $_POST['action'], 
                    $search, 
                    $sort, 
                    $direction
                );
        }
        
        // Notification pour la recherche de fournisseurs
        $notification = $this->getSearchNotification($search, $suppliers, $notification);
        
        // Rendu de la vue
        $this->render('provider/manage.twig', [
            'action' => $action,
            'suppliers' => $suppliers,
            'currentSupplier' => $currentSupplier,
            'search' => $search,
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
     * Affiche la page de création de fournisseur
     */
    public function create() {
        $this->checkAuth();
        
        if (!$this->isManager()) {
            $this->redirect('provider', 'index');
            return;
        }
        
        $this->render('provider/create.twig', [
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Ajouter un nouveau fournisseur'
        ]);
    }
    
    /**
     * Affiche la page détaillée d'un fournisseur
     */
    public function view() {
        $this->checkAuth();
        
        $segments = explode('/', $_GET['uri'] ?? '');
        $pathId = isset($segments[2]) ? intval($segments[2]) : null;
        
        $id = $_GET['id'] ?? $pathId; // Use ID from path if not in query string
        
        if (!$id) {
            $this->redirect('provider', 'index');
            return;
        }
        
        $supplier = $this->supplierModel->getSupplier($id);
        if (!$supplier) {
            $this->redirect('provider', 'index');
            return;
        }
        
        $this->render('provider/view.twig', [
            'supplier' => $supplier,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Détails du fournisseur: ' . $supplier->name
        ]);
    }
    
    /**
     * Traite la création d'un nouveau fournisseur
     * @return array [message de succès, erreurs]
     */
    private function handleCreate() {
        if (!$this->isManager()) {
            return ["", ["Vous n'avez pas les permissions nécessaires"]];
        }
        
        $errors = [];
        
        // Validation des données
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $contact_name = $_POST['contact_name'] ?? '';
        
        // Vérifications basiques
        if (empty($name)) {
            $errors[] = "Le nom est obligatoire";
        }
        if (empty($email)) {
            $errors[] = "L'email est obligatoire";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email n'est pas valide";
        }
        
        // Si aucune erreur, créer le fournisseur
        if (empty($errors)) {
            $success = $this->supplierModel->createSupplier(
                $name, $email, $phone, $address, $contact_name
            );
            
            if ($success) {
                return ["Fournisseur \"$name\" ajouté avec succès", []];
            } else {
                return ["", ["Erreur lors de l'ajout du fournisseur"]];
            }
        }
        
        return ["", $errors];
    }
    
    /**
     * Traite la mise à jour d'un fournisseur
     * @return array [message de succès, erreurs, fournisseur mise à jour]
     */
    private function handleUpdate() {
        if (!$this->isManager()) {
            return ["", ["Vous n'avez pas les permissions nécessaires"], null];
        }
        
        $errors = [];
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            return ["", ["ID du fournisseur manquant"], null];
        }
        
        // Récupération du fournisseur existant
        $supplier = $this->supplierModel->getSupplier($id);
        if (!$supplier) {
            return ["", ["Fournisseur introuvable"], null];
        }
        
        // Validation des données
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $contact_name = $_POST['contact_name'] ?? '';
        
        // Vérifications basiques
        if (empty($name)) {
            $errors[] = "Le nom est obligatoire";
        }
        if (empty($email)) {
            $errors[] = "L'email est obligatoire";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email n'est pas valide";
        }
        
        // Si aucune erreur, mettre à jour le fournisseur
        if (empty($errors)) {
            $success = $this->supplierModel->updateSupplier(
                $id, $name, $email, $phone, $address, $contact_name
            );
            
            if ($success) {
                $updatedSupplier = $this->supplierModel->getSupplier($id);
                return ["Fournisseur \"$name\" mis à jour avec succès", [], $updatedSupplier];
            } else {
                return ["", ["Erreur lors de la mise à jour du fournisseur"], $supplier];
            }
        }
        
        return ["", $errors, $supplier];
    }
    
    /**
     * Traite la suppression d'un fournisseur
     * @return array [message de succès, erreurs]
     */
    private function handleDelete() {
        if (!$this->isManager()) {
            return ["", ["Vous n'avez pas les droits nécessaires pour supprimer un fournisseur"]];
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            return ["", ["ID du fournisseur manquant"]];
        }
        
        $supplier = $this->supplierModel->getSupplier($id);
        if (!$supplier) {
            return ["", ["Fournisseur introuvable"]];
        }
        
        $name = $supplier->name;
        $success = $this->supplierModel->deleteSupplier($id);
        
        if ($success) {
            return ["Fournisseur \"$name\" supprimé avec succès", []];
        } else {
            return ["", ["Erreur lors de la suppression du fournisseur"]];
        }
    }

    /**
     * Traite la soumission du formulaire et renvoie les données mises à jour
     */
    private function handleFormSubmission($action, $search, $sort, $direction) {
        $errors = [];
        $success = '';
        $notification = $this->initNotification();
        $currentSupplier = null;
        
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
                    $action = 'list'; // Retour à la liste après création
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
                [$success, $errors, $currentSupplier] = $this->handleUpdate();
                if (empty($errors)) {
                    $notification = [
                        'type' => 'success',
                        'title' => 'Succès',
                        'message' => $success,
                        'show' => true
                    ];
                    $action = 'list'; // Retour à la liste après mise à jour
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
                $action = 'list'; // Retour à la liste après suppression/tentative
                break;
        }
        
        // Actualiser la liste des fournisseurs
        $suppliers = $this->getFilteredSortedSuppliers($search, $sort, $direction);
        
        return [$action, $success, $errors, $notification, $currentSupplier, $suppliers];
    }
    
    /**
     * Récupère les fournisseurs filtrés selon les critères
     */
    private function getFilteredSuppliers($search) {
        if (!empty($search)) {
            return $this->supplierModel->getFilteredSuppliers($search);
        } else {
            return $this->supplierModel->getAllSuppliers();
        }
    }
    
    /**
     * Récupère les fournisseurs filtrés et triés selon les critères
     */
    private function getFilteredSortedSuppliers($search, $sort, $direction) {
        $suppliers = $this->getFilteredSuppliers($search);
        return $this->sortSuppliers($suppliers, $sort, $direction);
    }
    
    /**
     * Trie les fournisseurs selon les critères
     */
    private function sortSuppliers($suppliers, $sort, $direction) {
        usort($suppliers, function($a, $b) use ($sort, $direction) {
            if ($direction === 'asc') {
                return $a->$sort <=> $b->$sort;
            } else {
                return $b->$sort <=> $a->$sort;
            }
        });
        
        return $suppliers;
    }
    
    /**
     * Vérifie si l'utilisateur est administrateur
     */
    private function isAdmin() {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin';
    }
    
    /**
     * Vérifie si l'utilisateur est manager ou administrateur
     */
    private function isManager() {
        return isset($_SESSION['user']['role']) && 
              ($_SESSION['user']['role'] === 'Manager' || $_SESSION['user']['role'] === 'Admin');
    }
    
    /**
     * Initialise un objet de notification vide
     */
    private function initNotification() {
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
    private function getPageTitle($action) {
        switch ($action) {
            case 'create': return 'Ajouter un fournisseur';
            case 'edit': return 'Modifier un fournisseur';
            case 'view': return 'Détails du fournisseur';
            case 'delete': return 'Supprimer un fournisseur';
            default: return 'Gestion des fournisseurs';
        }
    }
    
    /**
     * Génère une notification pour la recherche de fournisseurs
     */
    private function getSearchNotification($search, $suppliers, $notification) {
        if ($search && $notification['type'] === '') {
            $count = count($suppliers);
            if ($count > 0) {
                return [
                    'type' => 'info',
                    'title' => 'Recherche',
                    'message' => "Votre recherche a retourné $count fournisseur(s)",
                    'show' => true
                ];
            } else {
                return [
                    'type' => 'warning',
                    'title' => 'Recherche',
                    'message' => "Aucun fournisseur trouvé pour votre recherche",
                    'show' => true
                ];
            }
        }
        return $notification;
    }
}