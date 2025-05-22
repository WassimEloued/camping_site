<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class EventController extends AbstractController
{
    #[Route('', name: 'admin_events')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        
        return $this->render('admin/events/index.html.twig', [
            'events' => $events,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_event_delete', methods: ['POST'])]
    public function delete(Event $event, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($event);
        $entityManager->flush();
        
        $this->addFlash('success', 'Event deleted successfully.');
        return $this->redirectToRoute('admin_events');
    }
} 