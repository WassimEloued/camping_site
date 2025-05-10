<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LoginController extends AbstractController
{
    use TargetPathTrait;

    #[Route('/', name: 'app_login')]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        UserRepository $userRepository
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'admin_dashboard' : 'user_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('_username');
            $password = $request->request->get('_password');

            $user = $userRepository->findOneBy(['email' => $email]);

            try {
                if (!$user) {
                    throw new AuthenticationException('User not found.');
                }

                if ($user->getPassword() !== $password) {
                    throw new AuthenticationException('Invalid credentials.');
                }

                $this->container->get('security.token_storage')->setToken(
                    new UsernamePasswordToken($user, 'main', $user->getRoles())
                );

                $targetPath = $this->getTargetPath($request->getSession(), 'main');
                if ($targetPath) {
                    return $this->redirect($targetPath);
                }

                return $this->redirectToRoute($user->isAdmin() ? 'admin_dashboard' : 'user_dashboard');
            } catch (AuthenticationException $e) {
                $error = $e;
            }
        } else {
            $error = $authenticationUtils->getLastAuthenticationError();
            $lastUsername = $authenticationUtils->getLastUsername();
        }

        return $this->render('registration/login.html.twig', [
            'last_username' => $lastUsername ?? '',
            'error' => $error ?? null
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This will be intercepted by the logout key on your firewall.');
    }
}
