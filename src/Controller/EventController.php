<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/', name: 'event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy(['status' => 'approved']);
        
        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/my-events', name: 'event_my_events', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myEvents(): Response
    {
        $user = $this->getUser();
        
        return $this->render('event/my_events.html.twig', [
            'joinedEvents' => $user->getJoinedEvents(),
            'createdEvents' => $user->getCreatedEvents(),
        ]);
    }

    #[Route('/new', name: 'event_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $entityManager, ParameterBagInterface $params): Response
    {
        $event = new Event();
        $event->setCreator($this->getUser());
        $event->setStatus('pending');
        
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $params->get('event_images_directory'),
                        $newFilename
                    );
                    $event->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading photo: '.$e->getMessage());
                    return $this->redirectToRoute('event_create');
                }
            }

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Event created successfully and waiting for approval.');
            return $this->redirectToRoute('event_my_events');
        }

        return $this->render('event/create.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        $isParticipant = false;
        if ($this->getUser()) {
            $isParticipant = $event->getParticipants()->contains($this->getUser());
        }
        
        return $this->render('event/show.html.twig', [
            'event' => $event,
            'isParticipant' => $isParticipant,
        ]);
    }

    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        // Check if user is the creator or an admin
        if ($event->getCreator() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You can only edit your own events.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Event updated successfully.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/join', name: 'event_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(Event $event, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($event->getParticipants()->count() >= $event->getMaxParticipants()) {
            $this->addFlash('error', 'Sorry, this event is full.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        if ($event->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'You are already participating in this event.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $event->addParticipant($user);
        $entityManager->flush();

        $this->addFlash('success', 'You have successfully joined the event.');
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/leave', name: 'event_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(Event $event, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$event->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'You are not participating in this event.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $event->removeParticipant($user);
        $entityManager->flush();

        $this->addFlash('success', 'You have successfully left the event.');
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/approve', name: 'admin_event_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Event $event, EntityManagerInterface $entityManager): Response
    {
        $event->setStatus('approved');
        $entityManager->flush();

        $this->addFlash('success', 'Event has been approved.');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/{id}', name: 'event_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params
    ): Response {
        if ($event->getCreator() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You can only delete your own events');
            return $this->redirectToRoute('user_dashboard');
        }

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            try {
                if ($event->getPhoto()) {
                    $photoPath = $params->get('event_images_directory').'/'.$event->getPhoto();
                    if (file_exists($photoPath)) {
                        unlink($photoPath);
                    }
                }

                $entityManager->remove($event);
                $entityManager->flush();
                $this->addFlash('success', 'Event deleted successfully');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting event: '.$e->getMessage());
            }
        }

        return $this->redirectToRoute('user_dashboard');
    }

    #[Route('/{id}/reject', name: 'event_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('reject'.$event->getId(), $request->request->get('_token'))) {
            $event->setStatus('rejected');
            $entityManager->flush();
            $this->addFlash('success', 'Event rejected');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/events/bulk-approve', name: 'admin_events_bulk_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function bulkApproveEvents(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['eventIds']) || !is_array($data['eventIds'])) {
            return $this->json(['error' => 'Invalid data provided'], Response::HTTP_BAD_REQUEST);
        }

        $events = $entityManager->getRepository(Event::class)->findBy(['id' => $data['eventIds']]);
        
        foreach ($events as $event) {
            $event->setStatus('approved');
        }

        $entityManager->flush();

        return $this->json(['message' => 'Events approved successfully']);
    }

    #[Route('/admin/events/bulk-reject', name: 'admin_events_bulk_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function bulkRejectEvents(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['eventIds']) || !is_array($data['eventIds'])) {
            return $this->json(['error' => 'Invalid data provided'], Response::HTTP_BAD_REQUEST);
        }

        $events = $entityManager->getRepository(Event::class)->findBy(['id' => $data['eventIds']]);
        
        foreach ($events as $event) {
            $event->setStatus('rejected');
        }

        $entityManager->flush();

        return $this->json(['message' => 'Events rejected successfully']);
    }

    #[Route('/admin/events/filter', name: 'admin_events_filter', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function filterEvents(
        Request $request,
        EventRepository $eventRepository
    ): Response {
        $status = $request->query->get('status');
        $date = $request->query->get('date');
        $search = $request->query->get('search');

        $criteria = [];
        if ($status) {
            $criteria['status'] = $status;
        }
        if ($date) {
            $criteria['startDate'] = new \DateTime($date);
        }

        $events = $eventRepository->findBy($criteria);
        
        if ($search) {
            $events = array_filter($events, function($event) use ($search) {
                return stripos($event->getTitle(), $search) !== false ||
                       stripos($event->getDescription(), $search) !== false;
            });
        }

        $data = array_map(function($event) {
            return [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'startDate' => $event->getStartDate()->format('Y-m-d H:i:s'),
                'status' => $event->getStatus(),
                'creator' => $event->getCreator()->getEmail(),
                'participants' => count($event->getParticipants()),
                'maxParticipants' => $event->getMaxParticipants()
            ];
        }, $events);

        return $this->json($data);
    }
}