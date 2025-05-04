<?php
namespace App\Controller;

/**
 * Controller for basic pages like home, about, etc.
 */
class PageController extends Controller {
    
    /**
     * Constructor
     *
     * @param \Twig\Environment $twig The template engine
     */
    private $templateEngine;
    public function __construct(\Twig\Environment $twig)
    {
        $this->templateEngine = $twig;
    }
    
    /**
     * Display the home page
     */
    public function home()
    {
        echo $this->templateEngine->render('index.html.twig', [
            'title' => 'StockFlow - Inventory Management'
        ]);
    }
    
    /**
     * Display the about page
     */
    public function about()
    {
        echo $this->templateEngine->render('about.html.twig', [
            'title' => 'About StockFlow'
        ]);
    }
    
    /**
     * Display the contact page
     */
    public function contact()
    {
        echo $this->templateEngine->render('contact.html.twig', [
            'title' => 'Contact Us'
        ]);
    }
    
    /**
     * Process contact form submission
     */
    public function submitContact()
    {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        
        // Validate form data
        if (!$name || !$email || !$message) {
            echo $this->templateEngine->render('contact.html.twig', [
                'title' => 'Contact Us',
                'error' => 'All fields are required',
                'form' => [
                    'name' => $name,
                    'email' => $email,
                    'message' => $message
                ]
            ]);
            return;
        }
        
        // In a real application, you'd send an email or save to database here
        // For now, just render success message
        echo $this->templateEngine->render('contact.html.twig', [
            'title' => 'Contact Us',
            'success' => 'Thank you for your message! We will get back to you soon.',
            'form' => [
                'name' => '',
                'email' => '',
                'message' => ''
            ]
        ]);
    }
    
    /**
     * Display a 404 error page
     */
    public function notFound()
    {
        header("HTTP/1.0 404 Not Found");
        echo $this->templateEngine->render('error.html.twig', [
            'title' => 'Page Not Found',
            'message' => 'The page you are looking for could not be found.'
        ]);
    }
    
    /**
     * Display a dashboard/welcome page
     */
    public function dashboard()
    {
        echo $this->templateEngine->render('dashboard.html.twig', [
            'title' => 'Dashboard',
            'welcomeMessage' => 'Welcome to StockFlow Inventory Management System'
        ]);
    }
}
?>