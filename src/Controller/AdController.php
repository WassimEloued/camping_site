<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(EventRepository $eventRepo, UserRepository $userRepo): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'events' => $eventRepo->findAll(),
            'users' => $userRepo->findAll(),
            'pending_events' => $eventRepo->findBy(['status' => 'pending']),
        ]);
    }

    #[Route('/events/{id}/approve', name: 'admin_event_approve', methods: ['POST'])]
    public function approveEvent(Event $event, EntityManagerInterface $em): Response
    {
        $event->setStatus('approved');
        $em->flush();
        
        $this->addFlash('success', 'Event approved successfully');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/users/{id}/toggle-status', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggleUser(User $user, EntityManagerInterface $em): Response
    {
        $user->setIsActive(!$user->isActive());
        $em->flush();
        
        $this->addFlash('success', 'User status updated');
        return $this->redirectToRoute('admin_dashboard');
    }
}