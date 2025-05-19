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
            'allEvents' => $allEvents,
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
        try {
            $user->setIsActive(!$user->isActive());
            $entityManager->flush();
            return $this->json([
                'message' => 'User status updated successfully',
                'isActive' => $user->isActive()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error updating user status: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/user/create', name: 'admin_user_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
            return $this->json(['error' => 'Invalid data provided'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRole($data['role']);
        $user->setIsActive(true);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/admin/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        if ($user === $this->getUser()) {
            return $this->json(['error' => 'You cannot delete your own account'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $entityManager->remove($user);
            $entityManager->flush();
            return $this->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error deleting user: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['error' => 'Invalid data provided'], Response::HTTP_BAD_REQUEST);
        }

        try {
            if (isset($data['email'])) {
                $user->setEmail($data['email']);
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
            }
            if (isset($data['role'])) {
                $user->setRole($data['role']);
            }
            if (isset($data['isActive'])) {
                $user->setIsActive($data['isActive']);
            }

            $entityManager->flush();
            return $this->json(['message' => 'User updated successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error updating user: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/users/export', name: 'admin_users_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportUsers(
        UserRepository $userRepository
    ): Response {
        $users = $userRepository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'status' => $user->isActive() ? 'Active' : 'Inactive',
                'created_events' => count($user->getCreatedEvents()),
                'joined_events' => count($user->getJoinedEvents())
            ];
        }

        $response = new Response(json_encode($data, JSON_PRETTY_PRINT));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.json"');

        return $response;
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

    #[Route('/admin/event/{id}/delete', name: 'admin_event_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteEvent(
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $entityManager->remove($event);
            $entityManager->flush();
            return $this->json(['message' => 'Event deleted successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error deleting event: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/delete-account', name: 'user_delete_account', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAccount(
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $user = $this->getUser();
        
        try {
            // Delete all events created by the user
            foreach ($user->getCreatedEvents() as $event) {
                $entityManager->remove($event);
            }
            
            // Remove user from all joined events
            foreach ($user->getJoinedEvents() as $event) {
                $event->removeParticipant($user);
            }
            
            // Delete the user
            $entityManager->remove($user);
            $entityManager->flush();
            
            // Clear the session
            $request->getSession()->invalidate();
            
            return $this->json(['message' => 'Account deleted successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error deleting account: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/event/{id}/delete', name: 'user_event_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteUserEvent(
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        // Check if the user is the creator of the event
        if ($event->getCreator() !== $user) {
            return $this->json(['error' => 'You can only delete your own events'], Response::HTTP_FORBIDDEN);
        }
        
        try {
            $entityManager->remove($event);
            $entityManager->flush();
            return $this->json(['message' => 'Event deleted successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error deleting event: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}