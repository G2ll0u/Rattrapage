<?php
namespace App\Controller;

use App\Models\ProductModel;
use App\Models\CategoryModel;

class ProductController extends Controller {
    private $productModel;
    private $categoryModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Page d'accueil des produits - Affichage simple
     */
    public function index() {
        $this->checkAuth();
        
        // Paramètres de filtrage et tri
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $sort = $_GET['sort'] ?? 'name';
        $direction = $_GET['direction'] ?? 'asc';
        
        // Récupération et préparation des données
        $products = $this->getFilteredSortedProducts($search, $category, $sort, $direction);
        $categories = $this->categoryModel->getAllCategories();
        $categoryNames = $this->getCategoryNames($categories);
        $lowStockCount = $this->getLowStockCount($products);
        
        // Rendu de la vue
        $this->render('product/index.twig', [
            'products' => $products,
            'categories' => $categories,
            'categoryNames' => $categoryNames,
            'search' => $search,
            'selectedCategory' => $category,
            'sort' => $sort,
            'direction' => $direction,
            'lowStockCount' => $lowStockCount,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Liste des produits'
        ]);
    }
    
    /**
     * Gestion complète des produits (CRUD)
     */
    public function manage() {
        $this->checkAuth();
        
        $segments = explode('/', $_GET['uri'] ?? '');
        $pathId = isset($segments[2]) ? intval($segments[2]) : null;

        // Récupération des paramètres
        $action = $_GET['action'] ?? 'list';
        $id = $_GET['id'] ?? $pathId;
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $sort = $_GET['sort'] ?? 'name';
        $direction = $_GET['direction'] ?? 'asc';

        // Initialisation des variables
        $notification = $this->initNotification();
        $currentProduct = null;
        $errors = [];
        $success = '';
        
        // Récupération des données de base
        $categories = $this->categoryModel->getAllCategories();
        $products = $this->getFilteredSortedProducts($search, $category, $sort, $direction);
        $categoryNames = $this->getCategoryNames($categories);
        $lowStockCount = $this->getLowStockCount($products);
        
        // Traitement des actions CRUD
        if (in_array($action, ['edit', 'view', 'delete']) && $id) {
            $currentProduct = $this->productModel->getProduct($id);
            if (!$currentProduct) {
                $action = 'list';
                $notification = [
                    'type' => 'danger',
                    'title' => 'Erreur',
                    'message' => 'Produit introuvable',
                    'show' => true
                ];
            }
        }
        
        // Traitement des formulaires soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            [$action, $success, $errors, $notification, $currentProduct, $products] = 
                $this->handleFormSubmission(
                    $_POST['action'], 
                    $search, 
                    $category, 
                    $sort, 
                    $direction
                );
        }
        
        // Notification pour la recherche de produits
        $notification = $this->getSearchNotification($search, $category, $products, $notification);
        
        // Rendu de la vue
        $this->render('product/manage.twig', [
            'action' => $action,
            'products' => $products,
            'categories' => $categories,
            'categoryNames' => $categoryNames,
            'currentProduct' => $currentProduct,
            'search' => $search,
            'selectedCategory' => $category,
            'sort' => $sort,
            'direction' => $direction,
            'lowStockCount' => $lowStockCount,
            'errors' => $errors,
            'success' => $success,
            'notification' => $notification,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => $this->getPageTitle($action)
        ]);
    }
    
    /**
     * Affiche la page de création de produit
     */
    public function create() {
        $this->checkAuth();
        
        if (!$this->isManager()) {
            $this->redirect('product', 'index');
            return;
        }
        
        $categories = $this->categoryModel->getAllCategories();
        
        $this->render('product/create.twig', [
            'categories' => $categories,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Ajouter un nouveau produit'
        ]);
    }
    
    /**
     * Affiche la page détaillée d'un produit
     */
    public function view() {
        $this->checkAuth();
        
        $segments = explode('/', $_GET['uri'] ?? '');
        $pathId = isset($segments[2]) ? intval($segments[2]) : null;
    
        
        $id = $_GET['id'] ?? $pathId; // Use ID from path if not in query string
        
        if (!$id) {
            $this->redirect('product', 'index');
            return;
        }
        
        $product = $this->productModel->getProduct($id);
        if (!$product) {
            $this->redirect('product', 'index');
            return;
        }
        
        $category = $this->categoryModel->getCategory($product->ID_Category);
        
        $this->render('product/view.twig', [
            'product' => $product,
            'category' => $category,
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager(),
            'page_title' => 'Détails du produit: ' . $product->name
        ]);
    }
    
    /**
     * Traite la création d'un nouveau produit
     * @return array [message de succès, erreurs]
     */
    private function handleCreate() {
        if (!$this->isManager()) {
            return ["", ["Vous n'avez pas les permissions nécessaires"]];
        }
        
        $errors = [];
        
        // Validation des données
        $reference = $_POST['reference'] ?? '';
        $name = $_POST['name'] ?? '';
        $amount = intval($_POST['amount'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $last_delivery = !empty($_POST['last_delivery']) ? $_POST['last_delivery'] : date('Y-m-d');
        $alert_threshold = intval($_POST['alert_threshold'] ?? 0);
        $ID_Category = intval($_POST['ID_Category'] ?? 0);
        
        // Vérifications basiques
        if (empty($reference)) {
            $errors[] = "La référence est obligatoire";
        }
        if (empty($name)) {
            $errors[] = "Le nom est obligatoire";
        }
        if ($price <= 0) {
            $errors[] = "Le prix doit être supérieur à 0";
        }
        if ($ID_Category <= 0) {
            $errors[] = "Veuillez sélectionner une catégorie";
        }
        
        // Si aucune erreur, créer le produit
        if (empty($errors)) {
            $success = $this->productModel->createProduct(
                $reference, $name, $amount, $price, $last_delivery, 
                $alert_threshold, $ID_Category
            );
            
            if ($success) {
                return ["Produit \"$name\" ajouté avec succès", []];
            } else {
                return ["", ["Erreur lors de l'ajout du produit"]];
            }
        }
        
        return ["", $errors];
    }
    
    /**
     * Traite la mise à jour d'un produit
     * @return array [message de succès, erreurs, produit mise à jour]
     */
    private function handleUpdate() {
        if (!$this->isManager()) {
            return ["", ["Vous n'avez pas les permissions nécessaires"], null];
        }
        
        $errors = [];
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            return ["", ["ID du produit manquant"], null];
        }
        
        // Récupération du produit existant
        $product = $this->productModel->getProduct($id);
        if (!$product) {
            return ["", ["Produit introuvable"], null];
        }
        
        // Validation des données
        $reference = $_POST['reference'] ?? '';
        $name = $_POST['name'] ?? '';
        $amount = intval($_POST['amount'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $last_delivery = !empty($_POST['last_delivery']) ? $_POST['last_delivery'] : date('Y-m-d');
        $alert_threshold = intval($_POST['alert_threshold'] ?? 0);
        $ID_Category = intval($_POST['ID_Category'] ?? 0);
        
        // Vérifications basiques
        if (empty($reference)) {
            $errors[] = "La référence est obligatoire";
        }
        if (empty($name)) {
            $errors[] = "Le nom est obligatoire";
        }
        if ($price <= 0) {
            $errors[] = "Le prix doit être supérieur à 0";
        }
        if ($ID_Category <= 0) {
            $errors[] = "Veuillez sélectionner une catégorie";
        }
        
        // Si aucune erreur, mettre à jour le produit
        if (empty($errors)) {
            $success = $this->productModel->updateProduct(
                $id, $reference, $name, $amount, $price, 
                $last_delivery, $alert_threshold, $ID_Category
            );
            
            if ($success) {
                $updatedProduct = $this->productModel->getProduct($id);
                return ["Produit \"$name\" mis à jour avec succès", [], $updatedProduct];
            } else {
                return ["", ["Erreur lors de la mise à jour du produit"], $product];
            }
        }
        
        return ["", $errors, $product];
    }
    
    /**
     * Traite la suppression d'un produit
     * @return array [message de succès, erreurs]
     */
    private function handleDelete() {
        if (!$this->isAdmin()) {
            return ["", ["Vous n'avez pas les droits nécessaires pour supprimer un produit"]];
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            return ["", ["ID du produit manquant"]];
        }
        
        $product = $this->productModel->getProduct($id);
        if (!$product) {
            return ["", ["Produit introuvable"]];
        }
        
        $name = $product->name;
        $success = $this->productModel->deleteProduct($id);
        
        if ($success) {
            return ["Produit \"$name\" supprimé avec succès", []];
        } else {
            return ["", ["Erreur lors de la suppression du produit"]];
        }
    }

    /**
     * Traite la soumission du formulaire et renvoie les données mises à jour
     */
    private function handleFormSubmission($action, $search, $category, $sort, $direction) {
        $errors = [];
        $success = '';
        $notification = $this->initNotification();
        $currentProduct = null;
        
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
                [$success, $errors, $currentProduct] = $this->handleUpdate();
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
        
        // Actualiser la liste des produits
        $products = empty($errors) ? 
            $this->getFilteredSortedProducts($search, $category, $sort, $direction) : 
            $this->getFilteredProducts($search, $category);
        
        return [$action, $success, $errors, $notification, $currentProduct, $products];
    }
    
    /**
     * Récupère les produits filtrés selon les critères
     */
    private function getFilteredProducts($search, $category) {
        if (!empty($search)) {
            $products = $this->productModel->getFilteredProduct($search);
        } else {
            $products = $this->productModel->getAllProducts();
        }
        
        if (!empty($category)) {
            $products = array_filter($products, function($product) use ($category) {
                return $product->ID_Category == $category;
            });
        }
        
        return $products;
    }
    
    /**
     * Récupère les produits filtrés et triés selon les critères
     */
    private function getFilteredSortedProducts($search, $category, $sort, $direction) {
        $products = $this->getFilteredProducts($search, $category);
        return $this->sortProducts($products, $sort, $direction);
    }
    
    /**
     * Trie les produits selon les critères
     */
    private function sortProducts($products, $sort, $direction) {
        usort($products, function($a, $b) use ($sort, $direction) {
            if ($direction === 'asc') {
                return $a->$sort <=> $b->$sort;
            } else {
                return $b->$sort <=> $a->$sort;
            }
        });
        
        return $products;
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
            case 'create': return 'Ajouter un produit';
            case 'edit': return 'Modifier un produit';
            case 'view': return 'Détails du produit';
            case 'delete': return 'Supprimer un produit';
            default: return 'Gestion des produits';
        }
    }
    
    /**
     * Prépare un dictionnaire de noms de catégories indexé par ID
     */
    private function getCategoryNames($categories) {
        $categoryNames = [];
        foreach ($categories as $cat) {
            $categoryNames[$cat->ID_Category] = $cat->name;
        }
        return $categoryNames;
    }
    
    /**
     * Compte les produits à faible stock
     */
    private function getLowStockCount($products) {
        return count(array_filter($products, function($product) {
            return $product->amount <= $product->alert_threshold;
        }));
    }
    
    /**
     * Génère une notification pour la recherche de produits
     */
    private function getSearchNotification($search, $category, $products, $notification) {
        if (($search || $category) && $notification['type'] === '') {
            $count = count($products);
            if ($count > 0) {
                return [
                    'type' => 'info',
                    'title' => 'Recherche',
                    'message' => "Votre recherche a retourné $count produit(s)",
                    'show' => true
                ];
            } else {
                return [
                    'type' => 'warning',
                    'title' => 'Recherche',
                    'message' => "Aucun produit trouvé pour votre recherche",
                    'show' => true
                ];
            }
        }
        return $notification;
    }
}