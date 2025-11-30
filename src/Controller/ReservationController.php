<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    private function checkAccess(Reservation $reservation): void
    {
        $user = $this->getUser();
        
        // Admin can access all
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return;
        }
        
        // Ouvrier can access if assigned to them
        if (in_array('ROLE_OVR', $user->getRoles())) {
            $ouvrier = $user->getOuvrier();
            if ($ouvrier && $reservation->getOuvrierId() === $ouvrier->getId()) {
                return;
            }
        }
        
        // Client can access their own reservations
        if (in_array('ROLE_USER', $user->getRoles())) {
            $client = $user->getClient();
            if ($client && $reservation->getClient() === $client) {
                return;
            }
        }
        
        throw $this->createAccessDeniedException();
    }

    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        // Admin sees all reservations
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $reservations = $reservationRepository->findAll();
        }
        // Ouvrier sees their assigned reservations
        elseif (in_array('ROLE_OVR', $user->getRoles())) {
            $ouvrier = $user->getOuvrier();
            if (!$ouvrier) {
                $this->addFlash('error', 'Vous devez être un ouvrier pour voir les réservations.');
                return $this->redirectToRoute('app_home');
            }
            $reservations = $reservationRepository->findBy(
                ['ouvrierId' => $ouvrier->getId()],
                ['date' => 'DESC', 'heure' => 'DESC']
            );
        }
        // Client sees their own reservations
        else {
            $client = $user->getClient();
            if (!$client) {
                $this->addFlash('error', 'Vous devez être un client pour voir les réservations.');
                return $this->redirectToRoute('app_home');
            }
            $reservations = $reservationRepository->findBy(
                ['client' => $client],
                ['date' => 'DESC', 'heure' => 'DESC']
            );
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ClientRepository $clientRepository): Response
    {
        $user = $this->getUser();
        
        // Only clients can create reservations
        if (in_array('ROLE_OVR', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Seuls les clients peuvent créer des réservations.');
            return $this->redirectToRoute('app_home');
        }
        
        $client = $user->getClient();
        if (!$client) {
            $this->addFlash('error', 'Vous devez être un client pour créer une réservation.');
            return $this->redirectToRoute('app_home');
        }

        $reservation = new Reservation();
        $reservation->setClient($client);
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set statut to "En attente" by default
            $reservation->setStatut('En attente');
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation créée avec succès!');
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        $this->checkAccess($reservation);

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $this->checkAccess($reservation);
        
        // Only clients and admins can edit
        $user = $this->getUser();
        if (in_array('ROLE_OVR', $user->getRoles())) {
            throw $this->createAccessDeniedException('Les ouvriers ne peuvent pas modifier les réservations.');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $this->checkAccess($reservation);
        
        // Only clients and admins can delete
        $user = $this->getUser();
        if (in_array('ROLE_OVR', $user->getRoles())) {
            throw $this->createAccessDeniedException('Les ouvriers ne peuvent pas supprimer les réservations.');
        }

        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
