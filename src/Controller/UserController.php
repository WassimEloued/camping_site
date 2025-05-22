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
    public function adminDashboard(UserRepository $userRepository, EventRepository $eventRepository): Response
    {
        // Basic stats
        $stats = [
            'total_users' => $userRepository->count([]),
            'total_events' => $eventRepository->count([]),
            'events_by_status' => [
                'approved' => $eventRepository->count(['status' => 'approved']),
                'pending' => $eventRepository->count(['status' => 'pending']),
                'cancelled' => $eventRepository->count(['status' => 'cancelled']),
            ],
            'admin_users' => $userRepository->count(['role' => 'ROLE_ADMIN']),
            'regular_users' => $userRepository->count(['role' => 'ROLE_USER']),
            'approved_events' => $eventRepository->count(['status' => 'approved']),
            'pending_events' => $eventRepository->count(['status' => 'pending'])
        ];

        // Event participation stats
        $events = $eventRepository->findAll();
        $totalParticipants = 0;
        $eventsWithParticipants = 0;
        $maxParticipantsEvent = null;
        $maxParticipantsCount = 0;

        foreach ($events as $event) {
            $participantCount = $event->getParticipants()->count();
            $totalParticipants += $participantCount;
            
            if ($participantCount > 0) {
                $eventsWithParticipants++;
            }
            
            if ($participantCount > $maxParticipantsCount) {
                $maxParticipantsCount = $participantCount;
                $maxParticipantsEvent = $event;
            }
        }

        $stats['participation'] = [
            'total_participants' => $totalParticipants,
            'events_with_participants' => $eventsWithParticipants,
            'average_participants' => $stats['total_events'] > 0 ? round($totalParticipants / $stats['total_events'], 1) : 0,
            'max_participants_event' => $maxParticipantsEvent ? [
                'title' => $maxParticipantsEvent->getTitle(),
                'count' => $maxParticipantsCount
            ] : null
        ];

        // Event creation stats
        $activeCreators = [];
        foreach ($events as $event) {
            $creatorId = $event->getCreator()->getId();
            if (!isset($activeCreators[$creatorId])) {
                $activeCreators[$creatorId] = [
                    'user' => $event->getCreator(),
                    'count' => 0,
                    'approved' => 0
                ];
            }
            $activeCreators[$creatorId]['count']++;
            if ($event->getStatus() === 'approved') {
                $activeCreators[$creatorId]['approved']++;
            }
        }

        // Sort creators by event count
        uasort($activeCreators, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        $stats['creators'] = [
            'total_creators' => count($activeCreators),
            'top_creators' => array_slice($activeCreators, 0, 5, true)
        ];

        // Recent events and users
        $recent_events = $eventRepository->findBy([], ['startDate' => 'DESC'], 5);
        $recent_users = $userRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recent_events' => $recent_events,
            'recent_users' => $recent_users,
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