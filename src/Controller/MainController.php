<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig', []);
    }

    #[Route('/welcome', name: 'app_after_authenticate')]
    public function welcomeAfterAuthenticate(): Response
    {
        return $this->render('main/welcome.html.twig', []);
    }

    // Redirige vers Github
    #[Route('/connect/github', name: 'connect_github_start')]
    public function connectWithGithub(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('github')->redirect(['user:email'], []);
    }

    // Après Github on est redirigé ici (param "redirect_route" dans fichier knpu_oauth2_client.yaml)
    #[Route('/connect/github/check', name: 'connect_github_check')]
    public function checkWithGithub(Request $request, ClientRegistry $clientRegistry)
    {
        // Si on veut authentifier un user, laissez cette méthode vide et créer un Guard Authenticator
        
        // $client = $clientRegistry->getClient('github');
        // $user = $client->fetchUser();
        // dd($user);
    }
}
