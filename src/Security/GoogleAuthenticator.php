<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    // use TargetPathTrait;

    // public const LOGIN_ROUTE = 'app_login';

    public function __construct(private ClientRegistry $clientRegistry, private EntityManagerInterface $entityManager, private RouterInterface $router)
    {
    }

    public function supports(Request $request): ?bool
    {
        // Permet de savoir si la route courante matche avec la check ROUTE
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);
        // dd($accessToken);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($client, $accessToken) {
                /** @var User $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                // 1. Déjà connecté avec Github ??
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy([
                    'googleId' => $googleUser->getId()
                ]);

                if($existingUser) {
                    return $existingUser;
                }

                // 2. Sinon y a t-il un user avec l'email
                $user = $this->entityManager->getRepository(User::class)->findOneBy([
                    'email' => $email
                ]);

                // 3. Sinon on enregistre le user en DB
                $user->setGoogleId($googleUser->getId());
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->router->generate('app_after_authenticate');

        return new RedirectResponse($targetUrl);
        
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    // Called when authentication is needed, but it's not sent.
    // This redirects to the 'login'.
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/connect/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
