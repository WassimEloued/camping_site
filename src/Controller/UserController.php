<?php

namespace App\Controller;

use App\Entity\Event;  
use Doctrine\ORM\EntityManagerInterface;  
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\UserProfileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/user/dashboard', name: 'user_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function userDashboard(): Response
    {
        $user = $this->getUser();
        
        return $this->render('user/dashboard.html.twig', [
            'user' => $user,
            'createdEvents' => $user->getCreatedEvents(),
            'joinedEvents' => $user->getJoinedEvents(),
        ]);
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(
        UserRepository $userRepository,
        EventRepository $eventRepository
    ): Response {
        // Get all users and events
        $allUsers = $userRepository->findAll();
        $allEvents = $eventRepository->findAll();
        $approvedEvents = $eventRepository->findBy(['status' => 'approved']);
        $pendingEvents = $eventRepository->findBy(['status' => 'pending']);
        $rejectedEvents = $eventRepository->findBy(['status' => 'rejected']);

        // Calculate statistics
        $stats = [
            'total_users' => count($allUsers),
            'total_events' => count($allEvents),
            'approved_events' => count($approvedEvents),
            'pending_events' => count($pendingEvents),
            'rejected_events' => count($rejectedEvents),
            'active_users' => count(array_filter($allUsers, fn($user) => $user->isActive())),
            'admin_users' => count(array_filter($allUsers, fn($user) => $user->getRole() === 'ROLE_ADMIN')),
            'regular_users' => count(array_filter($allUsers, fn($user) => $user->getRole() === 'ROLE_USER')),
            'events_by_status' => [
                'approved' => count($approvedEvents),
                'pending' => count($pendingEvents),
                'rejected' => count($rejectedEvents)
            ],
            'recent_events' => $eventRepository->findBy([], ['startDate' => 'DESC'], 5),
            'recent_users' => $userRepository->findBy([], ['id' => 'DESC'], 5)
        ];

        return $this->render('admin/dashboard.html.twig', [
            'allUsers' => $allUsers,
            'approvedEvents' => $approvedEvents,
            'pendingEvents' => $pendingEvents,
            'stats' => $stats
        ]);
    }

    #[Route('/admin/user/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleUserStatus(
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'User %s has been %s',
            $user->getEmail(),
            $user->isActive() ? 'activated' : 'deactivated'
        ));

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/events/{id}/reject', name: 'admin_event_reject', methods: ['POST'])]
    public function rejectEvent(Event $event, EntityManagerInterface $em): Response
    {
        $event->setStatus('rejected');
        $em->flush();

        $this->addFlash('success', 'Event rejected successfully');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/approved-events', name: 'admin_approved_events')]
    public function showApprovedEvents(EventRepository $eventRepo): Response
    {
        $approvedEvents = $eventRepo->findBy(['status' => 'approved']);
        
        return $this->render('admin/approved_events.html.twig', [
            'approvedEvents' => $approvedEvents,
        ]);
    }

    #[Route('/profile/edit', name: 'user_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password change if provided
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Your profile has been updated successfully.');

            return $this->redirectToRoute('user_dashboard');
        }

        return $this->render('user/edit_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}