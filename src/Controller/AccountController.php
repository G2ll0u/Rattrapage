<?php
// app/controller/GestionUtilisateursController.php

namespace App\Controller;

use App\Controller\Controller;
use App\Models\AccountModel;
use PDO;

class AccountController extends Controller
{
    /**
     * Affichage de la gestion des utilisateurs (Admin ou pilote), avec pagination
     */
    private $AccountModel;
    public function __construct()
    {
        parent::__construct();
        $this->AccountModel = new AccountModel;
    }
    public function index()
    {
        // Vérification des droits d'accès
        $this->checkAuth(['Admin']);

        // On récupère, s'il existe, le résultat d'une recherche stocké en session
        $search_result = $_SESSION['search_result'] ?? null;
        unset($_SESSION['search_result']);


        // On récupère tous les utilisateurs
        $total = $this->AccountModel->getAllAccounts();


        // Rendu de la vue Twig
        $this->render('gestion_utilisateurs/index.twig', [
            'total'         => $total,
            'search_result' => $search_result
        ]);
    }

    /**
     * Création d'un utilisateur (Admin ou pilote)
     */
    public function create()
    {
        $this->checkAuth(['Admin']);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $nom      = trim($_POST["nom"]);
            $prenom   = trim($_POST["prenom"]);
            $email    = trim($_POST["email"]);
            $role     = trim($_POST["role"]);
            $password = password_hash($_POST["password"], PASSWORD_BCRYPT);


            if (!empty($nom) && !empty($prenom) && !empty($email) && !empty($password) && !empty($role)) {
                $this->AccountModel->createAccount($nom, $prenom, $email, $password, $role);
                $created = true;
            } else {
                $_SESSION["error"] = "Veuillez remplir tous les champs.";
                $created = false;
            }

            $this->redirect('gestionutilisateurs', 'index', $created ? ['notif' => 'created'] : ['notif' => 'error']);
        }
    }

    /**
     * Modification d'un utilisateur
     */
    public function update()
    {
        $this->checkAuth(['Admin']);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $id     = $_POST["id"];
            $nom    = trim($_POST["nom"])    ?? null;
            $prenom = trim($_POST["prenom"]) ?? null;
            $email  = trim($_POST["email"])  ?? null;
            $password = password_hash($_POST["password"], PASSWORD_BCRYPT);


            $this->AccountModel->updateAccount($id, $nom, $prenom, $email, $password);
            $this->redirect('gestionutilisateurs', 'index', ['notif' => 'updated']);
            header('Location: ' . BASE_URL . 'gestion-utilisateurs?notif=updated');
        }
    }

    /**
     * Suppression d'un utilisateur
     */
    public function delete()
    {
        $this->checkAuth(['Admin']);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $id = $_POST["id"];


            $this->AccountModel->deleteAccount($id);
            $_SESSION["message"] = "Utilisateur supprimé avec succès.";
            $this->redirect('gestionutilisateurs', 'index', ['notif' => 'deleted']);
        }
    }

    /**
     * Recherche d'un utilisateur
     */
    public function search()
    {
        $this->checkAuth(['Admin']);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $searchQuery = trim($_POST["search_query"]);

            $search_result = (!empty($searchQuery))
            ? $this->AccountModel->getFilteredAccount($searchQuery)
            : [];

            // On stocke le résultat de la recherche en session
            $_SESSION['search_result'] = $search_result;

            $this->redirect('gestionutilisateurs', 'index', ['notif' => 1]);
        } else {
            echo "Veuillez utiliser le formulaire pour effectuer une recherche.";
        }
    }

}