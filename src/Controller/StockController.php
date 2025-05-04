<?php
namespace App\Controller;

use App\Models\ProductModel;

class StockController extends Controller {
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new ProductModel();
    }
    
    /**
     * Lists all products with their stock amounts
     */
    public function index() {
        $this->checkAuth(); // Ensure user is authenticated
        
        // Get all products from the database
        $products = $this->productModel->getAllProducts();
        
        // Render the view with products
        $this->render('stock/index.twig', [
            'products' => $products,
            'page_title' => 'Inventaire'
        ]);
    }
}