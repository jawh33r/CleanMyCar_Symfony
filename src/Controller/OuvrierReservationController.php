<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\TakeReservationType;
use App\Repository\ReservationRepository;
use App\Repository\OuvrierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ouvrier/reservations', name: 'app_ouvrier_reservations')]
#[IsGranted('ROLE_OVR')]
class OuvrierReservationController extends AbstractController
{
    #[Route('/', name: '_index')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $ouvrier = $user->getOuvrier();
        
        if (!$ouvrier) {
            $this->addFlash('error', 'Vous devez être un ouvrier pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        // Get all reservations
        $allReservations = $reservationRepository->findAll();
        
        // Get reservations assigned to this ouvrier
        $myReservations = $reservationRepository->findBy(
            ['ouvrierId' => $ouvrier->getId()],
            ['date' => 'DESC', 'heure' => 'DESC']
        );

        return $this->render('ouvrier/reservations.html.twig', [
            'allReservations' => $allReservations,
            'myReservations' => $myReservations,
            'ouvrier' => $ouvrier,
        ]);
    }

    #[Route('/{id}/take', name: '_take', methods: ['GET', 'POST'])]
    public function takeReservation(
        Request $request,
        Reservation $reservation, 
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $ouvrier = $user->getOuvrier();
        
        if (!$ouvrier) {
            $this->addFlash('error', 'Vous devez être un ouvrier.');
            return $this->redirectToRoute('app_home');
        }

        if ($reservation->getOuvrierId() !== null) {
            $this->addFlash('warning', 'Cette réservation est déjà assignée à un ouvrier.');
            return $this->redirectToRoute('app_ouvrier_reservations_index');
        }

        $form = $this->createForm(TakeReservationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montant = $form->get('montant')->getData();
            
            $reservation->setOuvrierId($ouvrier->getId());
            $reservation->setMontant($montant);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation assignée avec succès! Montant: ' . number_format($montant, 2) . ' TND');
            return $this->redirectToRoute('app_ouvrier_reservations_index');
        }

        return $this->render('ouvrier/take_reservation.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/start', name: '_start', methods: ['POST'])]
    public function startReservation(
        Reservation $reservation, 
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $ouvrier = $user->getOuvrier();
        
        if (!$ouvrier || $reservation->getOuvrierId() !== $ouvrier->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas démarrer cette réservation.');
            return $this->redirectToRoute('app_ouvrier_reservations_index');
        }

        $reservation->setStatut('En cours');
        $entityManager->flush();

        $this->addFlash('success', 'Travail démarré!');
        return $this->redirectToRoute('app_ouvrier_reservations_index');
    }

    #[Route('/{id}/finish', name: '_finish', methods: ['POST'])]
    public function finishReservation(
        Reservation $reservation, 
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $ouvrier = $user->getOuvrier();
        
        if (!$ouvrier || $reservation->getOuvrierId() !== $ouvrier->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas terminer cette réservation.');
            return $this->redirectToRoute('app_ouvrier_reservations_index');
        }

        $reservation->setStatut('Terminée');
        $entityManager->flush();

        $this->addFlash('success', 'Travail terminé avec succès!');
        return $this->redirectToRoute('app_ouvrier_reservations_index');
    }
}

