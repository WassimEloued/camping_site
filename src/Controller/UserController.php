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

class UserController extends AbstractController
{
    #[Route('/dashboard', name: 'user_dashboard')]
    public function dashboard(
        EventRepository $eventRepository,
        UserRepository $userRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $freshUser = $userRepository->find($user->getId());
        

        if (!$freshUser) {
            $this->addFlash('error', 'User not found!');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/dashboard.html.twig', [
            'user' => $freshUser,
            'allUsers' => $userRepository->findAll(),
            'allEvents' => $eventRepository->findBy(['status' => 'approved']),
            'pendingEvents' => $eventRepository->findBy(['status' => 'pending']),
            'createdEvents' => $eventRepository->findByCreator($freshUser),
            'joinedEvents' => $freshUser->getJoinedEvents(),
            'availableEvents' => $eventRepository->findAvailableEvents($freshUser),
        ]);
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function adminDashboard(
        EventRepository $eventRepository,
        UserRepository $userRepository
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'allEvents' => $eventRepository->findAllApproved(),
            'pendingEvents' => $eventRepository->findPending(),
            'allUsers' => $userRepository->findAllWithEvents(),
        ]);
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

}