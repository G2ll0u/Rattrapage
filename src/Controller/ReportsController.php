<?php
namespace App\Controller;

use App\Models\ReportsModel;
use App\Models\ProductModel;
use App\Models\CategoryModel;

class ReportsController extends Controller
{
    private $stockReportModel;
    private $productModel;
    private $categoryModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->stockReportModel = new ReportsModel();
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Page d'accueil des rapports
     */
    public function index()
    {
        $this->checkAuth();
        
        $this->render('report/index.twig', [
            'page_title' => 'Tableau de bord - Rapports',
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager()
        ]);
    }
    
    /**
     * Rapport des mouvements de stock
     */
    public function movements()
    {
        $this->checkAuth();
        
        // Récupération des paramètres de filtrage
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $direction = $_GET['direction'] ?? '';
        
        // Récupération des données
        $movements = $this->stockReportModel->getStockMovements($startDate, $endDate, $direction);
        $categories = $this->categoryModel->getAllCategories();
        
        // Calcul de statistiques
        $totalIn = 0;
        $totalOut = 0;
        
        foreach ($movements as $move) {
            if ($move->direction == 'IN') {
                $totalIn += $move->qty;
            } else {
                $totalOut += $move->qty;
            }
        }
        
        $this->render('report/movement.twig', [
            'movements' => $movements,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'direction' => $direction,
            'categories' => $categories,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'netChange' => $totalIn - $totalOut,
            'page_title' => 'Rapport des mouvements de stock',
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager()
        ]);
    }
    
    /**
     * Rapport de prévision des achats
     */
    public function purchaseForecast()
    {
        $this->checkAuth();
        
        // Récupération des produits à commander
        $productsToOrder = $this->stockReportModel->getProductsToOrder();
        $categoryStats = $this->stockReportModel->getCategoryStats();
        
        // Calcul du montant total estimé pour les commandes
        $totalEstimate = 0;
        foreach ($productsToOrder as $product) {
            $totalEstimate += $product->quantity_to_order * $product->avg_unit_cost;
        }
        
        $this->render('report/purchase-forecast.twig', [
            'productsToOrder' => $productsToOrder,
            'categoryStats' => $categoryStats,
            'totalEstimate' => $totalEstimate,
            'page_title' => 'Prévisions d\'achats',
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager()
        ]);
    }
    
    /**
     * Rapport d'analyse des tendances
     */
    public function trends()
    {
        $this->checkAuth();
        
        // Récupération du nombre de mois à analyser
        $monthsBack = $_GET['months'] ?? 6;
        
        // Récupération des données
        $monthlyTrends = $this->stockReportModel->getMonthlyTrends($monthsBack);
        $productStats = $this->stockReportModel->getProductMovementStats(
            date('Y-m-d', strtotime("-$monthsBack months")),
            date('Y-m-d')
        );
        
        // Préparation des données pour les graphiques
        $chartData = [
            'labels' => [],
            'in' => [],
            'out' => [],
            'products' => []
        ];
        
        foreach ($monthlyTrends as $trend) {
            $chartData['labels'][] = $trend->month;
            $chartData['in'][] = $trend->monthly_in;
            $chartData['out'][] = $trend->monthly_out;
            $chartData['products'][] = $trend->products_moved;
        }
        
        $this->render('report/trends.twig', [
            'monthlyTrends' => $monthlyTrends,
            'productStats' => $productStats,
            'monthsBack' => $monthsBack,
            'chartData' => json_encode($chartData),
            'page_title' => 'Analyse des tendances',
            'isAdmin' => $this->isAdmin(),
            'isManager' => $this->isManager()
        ]);
    }
    
    /**
     * Vérification des rôles utilisateur
     */
    private function isAdmin()
    {
        return isset($_SESSION['user']['role']) && strtolower($_SESSION['user']['role']) === 'admin';
    }
    
    private function isManager()
    {
        if (!isset($_SESSION['user']['role'])) return false;
        
        $role = strtolower($_SESSION['user']['role']);
        return ($role === 'manager' || $role === 'admin');
    }
}
?>