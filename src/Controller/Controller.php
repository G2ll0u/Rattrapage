<?php
namespace App\Controller;

class Controller {
    protected $twig;
    protected $user;

    public function __construct() {
        // Vérifie si la session est active, sinon démarre-la
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Initialisation de Twig directement dans le contrôleur
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new \Twig\Environment($loader, [
            'debug' => true,
            'cache' => false, // Mettre un chemin pour la production
        ]);
        
        // Ajouter l'extension de débogage
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        
        // Définir BASE_URL si ce n'est pas déjà fait
        if (!defined('BASE_URL')) {
            define('BASE_URL', '/Rattrapage/');
        }
        
        // Ajouter la fonction url() à Twig
        $this->twig->addFunction(new \Twig\TwigFunction('url', function ($controller, $action, $params = []) {
            return $this->generateUrl($controller, $action, $params);
        }));
        
        // Ajouter la session comme variable globale
        $this->twig->addGlobal('session', $_SESSION ?? []);
        
        // Ajouter la date actuelle comme variable globale
        $this->twig->addGlobal('current_date', date('Y-m-d H:i:s'));
        
        // Cache l'information utilisateur
        $this->user = isset($_SESSION['user']) && !empty($_SESSION['user']) ? $_SESSION['user'] : null;
        
        // Ajouter l'utilisateur comme variable globale
        $this->twig->addGlobal('user', $this->user);
    }
    protected function render($template, $data = []) {
        // Appel à la méthode render de Twig : on injecte les variables globales comme 'session', 'user', 'BASE_URL'
        echo $this->twig->render($template, array_merge($data, [
            'session' => $_SESSION,
            'user' => $this->user,
            'BASE_URL' => BASE_URL
        ]));
    }

    protected function checkAuth(array $allowedRoles = []) {
        // Vérifie si l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            // S'il n'est pas connecté, on le redirige vers la page de connexion
            $this->redirect('user', 'login');
            exit;
        }
        
        // Si on a spécifié des rôles autorisés, on vérifie que l'utilisateur possède l'un de ces rôles
        if (!empty($allowedRoles)) {
            $userRole = $_SESSION['user']['role'] ?? '';
            if (!in_array($userRole, $allowedRoles)) {
                // En cas de rôle non autorisé, on redirige vers la page d'accueil
                $this->redirect('home', 'index');
                exit;
            }
        }
    }

    public function generateUrl($controller, $action, $params = []) {
        // Génère une URL en partant de BASE_URL (défini dans config.php),
        // puis en ajoutant contrôleur + action
        $url = BASE_URL . $controller . '/' . $action;
        
        // S'il y a un paramètre 'id', on l'ajoute directement dans l'URL (ex: /controller/action/12)
        if (isset($params['id'])) {
            $url .= '/' . $params['id'];
            unset($params['id']);
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    public function redirect($controller, $action, $params = []) {
        // Redirige en faisant un header Location vers l'URL générée
        $url = $this->generateUrl($controller, $action, $params);
        header("Location: $url");
        exit;
    }
}