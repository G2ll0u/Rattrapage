<?php
// app/controller/DashboardController.php

namespace App\Controller;

use App\Controller\Controller;
use App\Models\DashboardModel;

class DashboardController extends Controller {

    /**
     * Dashboard principal avec vue synthétique du stock
     */
    public function index() {
        // Accès pour tous les rôles authentifiés
        $this->checkAuth(['Admin', 'Manager', 'Employee']);
        
        // Récupération des statistiques générales du stock
        $stats = DashboardModel::getStockStats();
        
        $this->render('dashboard/index.twig', [
            'stats' => $stats,
            'page_title' => 'Tableau de bord - StockFlow'
        ]);
    }
    
    /**
     * Page spécifique des alertes de stock
     */
    public function alerts() {
        // Accès pour tous les rôles authentifiés
        $this->checkAuth(['Admin', 'Manager', 'Employee']);
        
        // Récupération des produits en alerte
        $alertProducts = DashboardModel::getAlertProducts();
        
        $this->render('dashboard/alerts.twig', [
            'alert_products' => $alertProducts,
            'page_title' => 'Alertes de stock - StockFlow'
        ]);
    }
    
    /**
     * Page d'évolution du stock
     */
    public function stockEvolution() {
        // Accès pour tous les rôles authentifiés
        $this->checkAuth(['Admin', 'Manager', 'Employee']);
        
        // Période par défaut : 30 jours
        $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
        
        // Validation de la période (entre 7 et 365 jours)
        $days = max(7, min(365, $days));
        
        // Récupération de l'évolution du stock
        $evolution = DashboardModel::getStockEvolution($days);
        
        $this->render('dashboard/evolution.twig', [
            'evolution' => $evolution,
            'days' => $days,
            'page_title' => "Évolution du stock sur $days jours - StockFlow"
        ]);
    }
    
    /**
     * Page de distribution par catégorie
     */
    public function categoryDistribution() {
        // Accès pour tous les rôles authentifiés
        $this->checkAuth(['Admin', 'Manager', 'Employee']);
        
        // Récupération de la distribution par catégorie
        $distribution = DashboardModel::getCategoryDistribution();
        
        $this->render('dashboard/categories.twig', [
            'distribution' => $distribution,
            'page_title' => 'Distribution par catégorie - StockFlow'
        ]);
    }
    
    /**
     * Page des statistiques de livraison
     * Accès limité aux Managers et Admins
     */
    public function deliveryStats() {
        // Accès restreint aux Manager et Admin
        $this->checkAuth(['Admin', 'Manager']);
        
        // Période par défaut : 6 mois
        $months = isset($_GET['months']) ? intval($_GET['months']) : 6;
        
        // Validation de la période (entre 1 et 24 mois)
        $months = max(1, min(24, $months));
        
        // Récupération des statistiques de livraison
        $deliveryStats = DashboardModel::getDeliveryStats($months);
        
        $this->render('dashboard/delivery.twig', [
            'delivery_stats' => $deliveryStats,
            'months' => $months,
            'page_title' => "Statistiques de livraison sur $months mois - StockFlow"
        ]);
    }
    
    /**
     * Export des données du tableau de bord au format CSV
     * Réservé aux managers et admins
     */
    public function exportData() {
        // Accès restreint aux Manager et Admin
        $this->checkAuth(['Admin', 'Manager']);
        
        $type = isset($_GET['type']) ? $_GET['type'] : 'stock';
        
        // En fonction du type demandé, récupérer les données appropriées
        switch ($type) {
            case 'alerts':
                $data = DashboardModel::getAlertProducts();
                $filename = 'alertes_stock_' . date('Y-m-d') . '.csv';
                break;
                
            case 'categories':
                $data = DashboardModel::getCategoryDistribution();
                $filename = 'distribution_categories_' . date('Y-m-d') . '.csv';
                break;
                
            case 'deliveries':
                $months = isset($_GET['months']) ? intval($_GET['months']) : 6;
                $data = DashboardModel::getDeliveryStats($months);
                $filename = 'statistiques_livraisons_' . date('Y-m-d') . '.csv';
                break;
                
            case 'stock':
            default:
                $stats = DashboardModel::getStockStats();
                $data = $stats['by_category']; // Exemple d'export par défaut
                $filename = 'synthese_stock_' . date('Y-m-d') . '.csv';
                break;
        }
        
        // Générer et télécharger le fichier CSV
        $this->generateCSV($data, $filename);
    }
    
    /**
     * Méthode utilitaire pour générer un fichier CSV
     */
    private function generateCSV($data, $filename) {
        // Définir les en-têtes pour forcer le téléchargement
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        // Créer un gestionnaire de fichier pointant vers php://output
        $output = fopen('php://output', 'w');
        
        // Vérifier si des données existent
        if (empty($data)) {
            fputcsv($output, ['Aucune donnée disponible']);
            fclose($output);
            return;
        }
        
        // Écrire l'en-tête CSV (les clés du premier élément)
        fputcsv($output, array_keys($data[0]));
        
        // Écrire chaque ligne de données
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}