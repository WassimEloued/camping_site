<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class PlainTextAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private RouterInterface $router
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username');
        $password = $request->request->get('_password');

        if (!$email || !$password) {
            throw new AuthenticationException('Please provide both email and password.');
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            throw new UserNotFoundException('User not found.');
        }

        // For now, compare plain text passwords directly
        if ($user->getPassword() !== $password) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function($email) {
                return $this->userRepository->findOneBy(['email' => $email]);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        
        // Redirect based on role
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('admin_dashboard'));
        }
        
        return new RedirectResponse($this->router->generate('user_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set('_security.last_error', $exception->getMessage());
        return new RedirectResponse($this->router->generate('app_login'));
    }
}