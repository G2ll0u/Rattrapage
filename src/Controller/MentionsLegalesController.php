<?php
namespace App\Controller;

class MentionsLegalesController extends Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Affiche la page des mentions légales
     */
    public function mentions() {
        $this->render('mentions-legales/mentions.twig');
    }
    
    /**
     * Affiche la page des conditions générales
     */
    public function conditions() {
        $this->render('mentions-legales/conditions.twig');
    }
    
    /**
     * Affiche la page de politique de confidentialité
     */
    public function confidentialite() {
        $this->render('mentions-legales/politique-confidentialite.twig');
    }
}
?>