<?php
// app/config/twig.php

// Définissez BASE_URL si ce n'est pas déjà fait
if (!defined('BASE_URL')) {
    // Ajustez ceci selon votre configuration
    define('BASE_URL', '/StockFlow/'); 
}

// Initialiser Twig
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'cache' => false, // Mettre un chemin pour la production
]);

// Ajouter l'extension de débogage
$twig->addExtension(new \Twig\Extension\DebugExtension());

// Ajouter la fonction url()
$twig->addFunction(new \Twig\TwigFunction('url', function ($controller, $action, $params = []) {
    $url = BASE_URL . $controller . '/' . $action;
    
    // S'il y a un paramètre 'id', on l'ajoute directement dans l'URL (ex: /controller/action/12)
    if (isset($params['id'])) {
        $url .= '/' . $params['id'];
        unset($params['id']);
    }
    
    // Ajouter les autres paramètres en tant que query string
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}));

// Ajouter la session comme variable globale
$twig->addGlobal('session', $_SESSION ?? []);

// Ajouter la date actuelle comme variable globale
$twig->addGlobal('current_date', date('Y-m-d H:i:s'));