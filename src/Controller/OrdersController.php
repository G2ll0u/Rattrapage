<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Models\OrdersModel;
use App\Models\OrderLineModel;
use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Models\SupplierModel;

class OrdersController extends Controller {

    private $ordersModel;
    private $orderLineModel;
    private $supplierModel;

    public function __construct() {
        parent::__construct();
        $this->ordersModel = new OrdersModel();
        $this->orderLineModel = new OrderLineModel();
        $this->supplierModel = new SupplierModel();
    }

    /**
     * Affiche la liste des commandes
     */
    public function index() {
        // Accès pour tous les rôles authentifiés
        $this->checkAuth(['Admin', 'Manager']);
        
        try {
            // Récupération des paramètres
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $provider = $_GET['provider'] ?? '';
            $sort = $_GET['sort'] ?? 'order_date';
            $direction = $_GET['direction'] ?? 'desc';
            
            // Récupération des données
            $suppliers = $this->supplierModel->getAllSuppliers();
            
            // Filtrage des commandes
            if (!empty($search) || !empty($status) || !empty($provider)) {
                $orders = $this->ordersModel->getFilteredOrders($search, $status, $provider);
            } else {
                $orders = $this->ordersModel->getAllOrders();
            }
            
            // Tri des commandes
            $orders = $this->sortOrders($orders, $sort, $direction);
            
            // Préparation des données pour l'affichage
            $supplierNames = [];
            foreach ($suppliers as $supplier) {
                $supplierNames[$supplier->ID_Provider] = $supplier->name;
            }
            
            // Commandes en attente
            $pendingOrders = array_filter($orders, function($order) {
                return $order->status === 'pending';
            });
            
            $this->render('orders/index.twig', [
                'page_title' => 'Liste des commandes',
                'orders' => $orders,
                'suppliers' => $suppliers,
                'supplierNames' => $supplierNames,
                'search' => $search,
                'selectedStatus' => $status,
                'selectedProvider' => $provider,
                'sort' => $sort,
                'direction' => $direction,
                'pendingCount' => count($pendingOrders),
                'statuses' => ['pending' => 'En attente', 'delivered' => 'Livrée', 'cancelled' => 'Annulée']
            ]);
        } catch (\Exception $e) {
            // Gérer l'erreur ici (journaliser, afficher un message, etc.)
            $this->render('orders/index.twig', [
                'page_title' => 'Liste des commandes',
                'orders' => [],
                'error' => 'Erreur lors du chargement des données: ' . $e->getMessage()
            ]);
        }
    }
    public function createLine()
    {
        $this->checkAuth(['Admin', 'Manager']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = trim($_POST['product_id']);
            $order_id = trim($_POST['order_id']);
            $quantity = trim($_POST['quantity']);
            $price = trim($_POST['price']);
            
        }
        if (!empty($product_id) && !empty($order_id) && !empty($quantity) && !empty($price)) {
            $this->orderLineModel->createOrderLine($product_id, $order_id, $quantity, $price);
            $created = true;
        } else {
            $_SESSION["error"] = "Veuillez remplir tous les champs.";
            $created = false;
        }
        $this->redirect('orders', 'index', $created ? ['notif' => 'created'] : ['notif' => 'error']);
    }
    public function createOrder()  
    {
        $this->checkAuth(['Admin', 'Manager']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_date = date('Y-m-d H:i:s');
            $expected_date = trim($_POST['expected_date']);
            $status = 'pending';
            $ID_Provider = trim($_POST['ID_Provider']);

            if (!empty($order_date) && !empty($expected_date) && !empty($status) && !empty($ID_Provider)) {
                $this->ordersModel->createOrders($order_date, $expected_date, $status, $ID_Provider);
                $created = true;
            } else {
                $_SESSION["error"] = "Veuillez remplir tous les champs.";
                $created = false;
            }
            $this->redirect('orders', 'index', $created ? ['notif' => 'created'] : ['notif' => 'error']);
    
        }
    }
    public function deleteOrder()
    {
        $this->checkAuth(['Admin', 'Manager']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_id = trim($_POST['order_id']);
            if (!empty($order_id)) {
                $this->ordersModel->deleteOrder($order_id);
                $this->orderLineModel->deleteAllOrderLines($order_id); // Suppression des lignes de commande associées
                $deleted = true;
            } else {
                $_SESSION["error"] = "Veuillez remplir tous les champs.";
                $deleted = false;
            }
            $this->redirect('orders', 'index', $deleted ? ['notif' => 'deleted'] : ['notif' => 'error']);
        }
    }
    public function deleteLine()
    {
        $this->checkAuth(['Admin', 'Manager']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_id = trim($_POST['order_id']);
            $product_id = trim($_POST['product_id']);
            if (!empty($line_id)) {
                $this->orderLineModel->deleteOrderLine($order_id, $product_id);
                $deleted = true;
            } else {
                $_SESSION["error"] = "Veuillez remplir tous les champs.";
                $deleted = false;
            }
            $this->redirect('orders', 'index', $deleted ? ['notif' => 'deleted'] : ['notif' => 'error']);
        }
    }
    public function updateOrder()
    {
        $this->checkAuth(['Admin', 'Manager']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_id = trim($_POST['order_id']);
            $order_date = null;
            $expected_date = trim($_POST['expected_date']) ?? null;
            $status = trim($_POST['status']) ?? null;
            $ID_Provider = trim($_POST['ID_Provider']);

            $this->ordersModel->updateOrders($order_id, $order_date, $expected_date, $status, $ID_Provider);
            $this->redirect('orders', 'index', ['notif' => 'updated']);
        }
    }
    public function updateLine()
    {
        $this->checkAuth(['Admin', 'Manager']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_id = trim($_POST['order_id']);
            $product_id = trim($_POST['product_id']);
            $quantity = trim($_POST['quantity'] ?? null);
            $price = trim($_POST['price'] ?? null);

            $this->orderLineModel->updateOrderLine($order_id, $product_id, $quantity, $price);
            $this->redirect('orders', 'index', ['notif' => 'updated']);
        }
    }
    /**
 * Trie les commandes selon les critères
 */
private function sortOrders($orders, $sort, $direction) {
    usort($orders, function($a, $b) use ($sort, $direction) {
        // Gestion spéciale pour les dates
        if (in_array($sort, ['order_date', 'expected_date'])) {
            $dateA = strtotime($a->$sort);
            $dateB = strtotime($b->$sort);
            if ($direction === 'asc') {
                return $dateA <=> $dateB;
            } else {
                return $dateB <=> $dateA;
            }
        }
        
        // Tri normal pour les autres champs
        if ($direction === 'asc') {
            return $a->$sort <=> $b->$sort;
        } else {
            return $b->$sort <=> $a->$sort;
        }
    });
    
    return $orders;
}
}