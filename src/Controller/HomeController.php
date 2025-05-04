<?php
namespace App\Controller;

class HomeController extends Controller {
    /**
     * Page d'accueil (ouverte à tous)
     */
    public function index() {
        if (isset($_SESSION['user'])) {
            $this->redirect('dashboard', 'index');
            return;
        }
        $this->render('home/index.twig', [
            'page_title' => 'Accueil - StockFlow'
        ]);
    }
    
    /**
     * Page 404 - Not Found
     */
    public function notFound() {
        header("HTTP/1.0 404 Not Found");
        $this->render('error/404.twig', [
            'page_title' => 'Page non trouvée'
        ]);
    }
}