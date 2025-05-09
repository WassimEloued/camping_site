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

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/create', name: 'event_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params
    ): Response {
        $event = new Event();
        $event->setCreator($this->getUser());
        $event->setStatus('pending');

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $photoFile = $form->get('photo')->getData();
                
                if ($photoFile) {
                    $safeFilename = bin2hex(random_bytes(10)) . '.' . $photoFile->guessExtension();
                    try {
                        $photoFile->move(
                            $params->get('event_images_directory'),
                            $safeFilename
                        );
                        $event->setPhoto($safeFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'File upload failed: '.$e->getMessage());
                    }
                }

                $entityManager->persist($event);
                $entityManager->flush();

                if ($this->isGranted('ROLE_ADMIN')) {
                    $event->setStatus('approved');
                    $entityManager->flush();
                    $this->addFlash('success', 'Event created and approved successfully!');
                } else {
                    $this->addFlash('success', 'Event created successfully! Waiting for admin approval.');
                }

                return $this->redirectToRoute('user_dashboard');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating event: '.$e->getMessage());
            }
        }

        return $this->render('event/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params
    ): Response {
        if ($event->getCreator() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You can only edit your own events');
            return $this->redirectToRoute('user_dashboard');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $photoFile = $form->get('photo')->getData();
                
                if ($photoFile) {
                    if ($event->getPhoto()) {
                        $oldPhoto = $params->get('event_images_directory').'/'.$event->getPhoto();
                        if (file_exists($oldPhoto)) {
                            unlink($oldPhoto);
                        }
                    }

                    $safeFilename = bin2hex(random_bytes(10)) . '.' . $photoFile->guessExtension();
                    $photoFile->move(
                        $params->get('event_images_directory'),
                        $safeFilename
                    );
                    $event->setPhoto($safeFilename);
                }

                if ($this->isGranted('ROLE_ADMIN') && $event->getStatus() === 'pending') {
                    $event->setStatus('approved');
                }

                $entityManager->flush();
                $this->addFlash('success', 'Event updated successfully!');
                return $this->redirectToRoute('event_show', ['id' => $event->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating event: '.$e->getMessage());
            }
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(Event $event, EventRepository $eventRepo): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $event->getStatus() !== 'approved') {
            throw $this->createAccessDeniedException('This event is not available');
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'isParticipant' => $event->getParticipants()->contains($this->getUser()),
            'similarEvents' => $eventRepo->findSimilarEvents($event),
        ]);
    }

    #[Route('/{id}/join', name: 'event_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        if ($event->getStatus() !== 'approved') {
            $this->addFlash('error', 'This event is not available for joining');
            return $this->redirectToRoute('user_dashboard');
        }

        $user = $this->getUser();

        if ($event->getParticipants()->count() >= $event->getMaxParticipants()) {
            $this->addFlash('error', 'This event is already full');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        if (!$event->getParticipants()->contains($user)) {
            $event->addParticipant($user);
            $entityManager->flush();
            $this->addFlash('success', 'You have joined the event!');
        }

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/leave', name: 'event_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if ($event->getParticipants()->contains($user)) {
            $event->removeParticipant($user);
            $entityManager->flush();
            $this->addFlash('success', 'You have left the event');
        }

        return $this->redirectToRoute('user_dashboard');
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

    #[Route('/{id}/approve', name: 'event_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('approve'.$event->getId(), $request->request->get('_token'))) {
            $event->setStatus('approved');
            $entityManager->flush();
            $this->addFlash('success', 'Event approved successfully');
        }

        return $this->redirectToRoute('admin_dashboard');
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
}