<?php
namespace App\Models;

class ReportsModel extends Model
{
    /**
     * Récupère les mouvements de stock avec les détails des produits et utilisateurs
     * @param string $startDate Format Y-m-d
     * @param string $endDate Format Y-m-d
     * @param string $direction IN, OUT ou null pour tous
     * @return array
     */
    public function getStockMovements($startDate = null, $endDate = null, $direction = null)
    {
        $sql = "SELECT sm.*, 
                p.name as product_name, p.reference, p.amount as current_stock, p.alert_threshold,
                u.name as user_lastname, u.first_name as user_firstname,
                pr.name as provider_name, c.name as category_name
                FROM stock_movement sm
                JOIN product p ON sm.ID_Product = p.ID_Product
                JOIN user_ u ON sm.ID_User = u.ID_User
                JOIN category c ON p.ID_Category = c.ID_Category
                LEFT JOIN provider pr ON sm.ID_Provider = pr.ID_Provider
                WHERE 1=1";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND sm.move_date >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND sm.move_date <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        if ($direction) {
            $sql .= " AND sm.direction = :direction";
            $params[':direction'] = $direction;
        }
        
        $sql .= " ORDER BY sm.move_date DESC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    /**
     * Récupère les statistiques de mouvements par produit
     * @param string $startDate Format Y-m-d
     * @param string $endDate Format Y-m-d
     * @return array
     */
    public function getProductMovementStats($startDate = null, $endDate = null)
    {
        $sql = "SELECT p.ID_Product, p.name as product_name, p.reference, p.amount as current_stock, 
                p.alert_threshold, c.name as category_name,
                SUM(CASE WHEN sm.direction = 'IN' THEN sm.qty ELSE 0 END) as total_in,
                SUM(CASE WHEN sm.direction = 'OUT' THEN sm.qty ELSE 0 END) as total_out,
                SUM(CASE WHEN sm.direction = 'IN' THEN sm.qty ELSE -sm.qty END) as net_change
                FROM product p
                LEFT JOIN stock_movement sm ON p.ID_Product = sm.ID_Product";
        
        if ($startDate || $endDate) {
            $sql .= " AND (";
            $conditions = [];
            $params = [];
            
            if ($startDate) {
                $conditions[] = "sm.move_date >= :start_date";
                $params[':start_date'] = $startDate;
            }
            
            if ($endDate) {
                $conditions[] = "sm.move_date <= :end_date";
                $params[':end_date'] = $endDate;
            }
            
            $sql .= implode(" AND ", $conditions) . ")";
        } else {
            $params = [];
        }
        
        $sql .= " JOIN category c ON p.ID_Category = c.ID_Category
                 GROUP BY p.ID_Product, p.name, p.reference, p.amount, p.alert_threshold, c.name
                 ORDER BY net_change ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    /**
     * Récupère les produits à commander (sous le seuil d'alerte)
     * @return array
     */
    public function getProductsToOrder()
    {
        $sql = "SELECT p.*, c.name as category_name,
                (p.alert_threshold - p.amount) as quantity_to_order,
                (SELECT pr.ID_Provider
                 FROM delivery d
                 JOIN provider pr ON d.ID_Provider = pr.ID_Provider
                 WHERE d.ID_Product = p.ID_Product
                 GROUP BY pr.ID_Provider
                 ORDER BY COUNT(*) DESC
                 LIMIT 1) as suggested_provider_id,
                (SELECT pr.name
                 FROM delivery d
                 JOIN provider pr ON d.ID_Provider = pr.ID_Provider
                 WHERE d.ID_Product = p.ID_Product
                 GROUP BY pr.ID_Provider
                 ORDER BY COUNT(*) DESC
                 LIMIT 1) as suggested_provider_name,
                (SELECT AVG(d.unit_cost)
                 FROM delivery d
                 WHERE d.ID_Product = p.ID_Product) as avg_unit_cost
                FROM product p
                JOIN category c ON p.ID_Category = c.ID_Category
                WHERE p.amount <= p.alert_threshold
                ORDER BY (p.alert_threshold - p.amount) DESC";
        
        $stmt = $this->query($sql, []);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    /**
     * Génère des statistiques par catégorie
     * @return array
     */
    public function getCategoryStats()
    {
        $sql = "SELECT c.ID_Category, c.name as category_name,
                COUNT(p.ID_Product) as product_count,
                SUM(p.amount) as total_stock,
                SUM(p.price * p.amount) as total_value,
                COUNT(CASE WHEN p.amount <= p.alert_threshold THEN 1 END) as low_stock_count
                FROM category c
                LEFT JOIN product p ON c.ID_Category = p.ID_Category
                GROUP BY c.ID_Category, c.name
                ORDER BY total_value DESC";
        
        $stmt = $this->query($sql, []);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    /**
     * Récupère les tendances mensuelles de mouvements de stock
     * @param int $monthsBack Nombre de mois à remonter
     * @return array
     */
    public function getMonthlyTrends($monthsBack = 6)
    {
        $sql = "SELECT 
                DATE_FORMAT(sm.move_date, '%Y-%m') as month,
                SUM(CASE WHEN sm.direction = 'IN' THEN sm.qty ELSE 0 END) as monthly_in,
                SUM(CASE WHEN sm.direction = 'OUT' THEN sm.qty ELSE 0 END) as monthly_out,
                COUNT(DISTINCT sm.ID_Product) as products_moved
                FROM stock_movement sm
                WHERE sm.move_date >= DATE_SUB(CURRENT_DATE, INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(sm.move_date, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->query($sql, [':months' => $monthsBack]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
?>