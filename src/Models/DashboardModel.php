<?php
// app/model/DashboardModel.php

namespace App\Models;

use App\Config\Database; 
use PDO;

class DashboardModel extends Model
{
    /**
     * Récupère l'ensemble des statistiques du stock
     */
    public static function getStockStats()
    {
        try {
            $pdo = Database::getInstance();
            
            // Synthèse globale du stock
            $stmtGlobal = $pdo->prepare("
                SELECT 
                    COUNT(*) AS total_products,
                    SUM(amount) AS total_items,
                    SUM(amount * price) AS total_value
                FROM product
            ");
            $stmtGlobal->execute();
            $global_stats = $stmtGlobal->fetch(PDO::FETCH_ASSOC);
            
            // Répartition par catégorie
            $stmtByCategory = $pdo->prepare("
                SELECT 
                    c.name AS category,
                    COUNT(p.ID_Product) AS product_count,
                    SUM(p.amount) AS total_items,
                    SUM(p.amount * p.price) AS total_value
                FROM product p
                JOIN category c ON p.ID_Category = c.ID_Category
                GROUP BY c.ID_Category
                ORDER BY total_items DESC
            ");
            $stmtByCategory->execute();
            $by_category = $stmtByCategory->fetchAll(PDO::FETCH_ASSOC);
            
            // Produits en alerte de stock
            $stmtAlerts = $pdo->prepare("
                SELECT 
                    p.ID_Product,
                    p.reference,
                    p.name,
                    p.amount,
                    p.alert_threshold,
                    c.name AS category,
                    CASE
                        WHEN p.amount = 0 THEN 'rupture'
                        WHEN p.amount <= p.alert_threshold THEN 'alerte'
                        ELSE 'normal'
                    END AS status
                FROM product p
                JOIN category c ON p.ID_Category = c.ID_Category
                WHERE p.amount <= p.alert_threshold
                ORDER BY p.amount ASC
            ");
            $stmtAlerts->execute();
            $stock_alerts = $stmtAlerts->fetchAll(PDO::FETCH_ASSOC);
            
            // Top 5 des produits les plus mouvementés
            $stmtTopMovements = $pdo->prepare("
                SELECT 
                    p.reference,
                    p.name,
                    c.name AS category,
                    COUNT(sm.ID_Move) AS movement_count,
                    SUM(CASE WHEN sm.direction = 'IN' THEN sm.qty ELSE 0 END) AS total_in,
                    SUM(CASE WHEN sm.direction = 'OUT' THEN sm.qty ELSE 0 END) AS total_out
                FROM stock_movement sm
                JOIN product p ON sm.ID_Product = p.ID_Product
                JOIN category c ON p.ID_Category = c.ID_Category
                GROUP BY p.ID_Product
                ORDER BY movement_count DESC
                LIMIT 5
            ");
            $stmtTopMovements->execute();
            $top_movements = $stmtTopMovements->fetchAll(PDO::FETCH_ASSOC);
            
            // Derniers mouvements de stock
            $stmtRecentMovements = $pdo->prepare("
                SELECT 
                    sm.move_date,
                    p.reference,
                    p.name,
                    sm.qty,
                    sm.direction,
                    u.first_name || ' ' || u.name AS user_name
                FROM stock_movement sm
                JOIN product p ON sm.ID_Product = p.ID_Product
                JOIN user u ON sm.ID_User = u.ID_User
                ORDER BY sm.move_date DESC, sm.ID_Move DESC
                LIMIT 10
            ");
            $stmtRecentMovements->execute();
            $recent_movements = $stmtRecentMovements->fetchAll(PDO::FETCH_ASSOC);
            
            // Commandes en attente
            $stmtPendingOrders = $pdo->prepare("
                SELECT 
                    o.ID_Orders,
                    o.order_date,
                    o.expected_date,
                    o.status,
                    pr.name AS provider_name,
                    COUNT(ol.ID_Product) AS product_count,
                    SUM(ol.qty * ol.unit_price) AS total_value
                FROM orders o
                JOIN provider pr ON o.ID_Provider = pr.ID_Provider
                JOIN order_line ol ON o.ID_Orders = ol.ID_Orders
                WHERE o.status = 'pending'
                GROUP BY o.ID_Orders
                ORDER BY o.order_date DESC
            ");
            $stmtPendingOrders->execute();
            $pending_orders = $stmtPendingOrders->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'global_stats' => $global_stats,
                'by_category' => $by_category,
                'stock_alerts' => $stock_alerts,
                'top_movements' => $top_movements,
                'recent_movements' => $recent_movements,
                'pending_orders' => $pending_orders
            ];
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de la récupération des statistiques du dashboard : " . $e->getMessage());
        }
    }
    
    /**
     * Récupère les produits en rupture de stock ou sous le seuil d'alerte
     */
    public static function getAlertProducts()
    {
        try {
            $pdo = Database::getInstance();
            
            // Produits en rupture ou sous seuil d'alerte
            $stmt = $pdo->prepare("
                SELECT 
                    p.ID_Product,
                    p.reference,
                    p.name,
                    p.amount,
                    p.alert_threshold,
                    p.price,
                    c.name AS category,
                    CASE
                        WHEN p.amount = 0 THEN 'danger'
                        WHEN p.amount <= p.alert_threshold THEN 'warning'
                    END AS alert_level
                FROM product p
                JOIN category c ON p.ID_Category = c.ID_Category
                WHERE p.amount <= p.alert_threshold
                ORDER BY alert_level ASC, p.amount ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de la récupération des produits en alerte : " . $e->getMessage());
        }
    }
    
    /**
     * Récupère l'évolution du stock sur une période donnée
     */
    public static function getStockEvolution($days = 30)
    {
        try {
            $pdo = Database::getInstance();
            
            $stmt = $pdo->prepare("
                SELECT 
                    date(sm.move_date) AS date,
                    SUM(CASE WHEN sm.direction = 'IN' THEN sm.qty ELSE 0 END) AS entries,
                    SUM(CASE WHEN sm.direction = 'OUT' THEN sm.qty ELSE 0 END) AS exits
                FROM stock_movement sm
                WHERE sm.move_date >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)
                GROUP BY date(sm.move_date)
                ORDER BY date(sm.move_date)
            ");
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de la récupération de l'évolution du stock : " . $e->getMessage());
        }
    }
    
    /**
     * Récupère la répartition des produits par catégorie
     */
    public static function getCategoryDistribution()
    {
        try {
            $pdo = Database::getInstance();
            
            $stmt = $pdo->prepare("
                SELECT 
                    c.name AS category,
                    COUNT(p.ID_Product) AS count,
                    SUM(p.amount) AS quantity,
                    SUM(p.amount * p.price) AS value
                FROM category c
                LEFT JOIN product p ON c.ID_Category = p.ID_Category
                GROUP BY c.ID_Category
                ORDER BY quantity DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de la récupération de la distribution par catégorie : " . $e->getMessage());
        }
    }
    
    /**
     * Récupère les statistiques de livraison
     */
    public static function getDeliveryStats($months = 6)
    {
        try {
            $pdo = Database::getInstance();
            
            $stmt = $pdo->prepare("
                SELECT 
                    date_format(d.delivery_date, '%Y-%m') AS month,
                    COUNT(d.ID_Delivery) AS delivery_count,
                    SUM(d.qty) AS total_quantity,
                    SUM(d.qty * d.unit_cost) AS total_cost,
                    COUNT(DISTINCT d.ID_Provider) AS provider_count
                FROM delivery d
                WHERE d.delivery_date >= DATE_SUB(CURRENT_DATE, INTERVAL :months MONTH)
                GROUP BY date_format(d.delivery_date, '%Y-%m')
                ORDER BY month
            ");
            $stmt->bindParam(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de la récupération des statistiques de livraison : " . $e->getMessage());
        }
    }
}
?>