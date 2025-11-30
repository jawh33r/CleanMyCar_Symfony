<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use App\Repository\ReservationRepository;
use App\Repository\OuvrierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/avis')]
class AvisController extends AbstractController
{
    #[Route('/', name: 'app_avis_index', methods: ['GET'])]
    public function index(AvisRepository $avisRepository, OuvrierRepository $ouvrierRepository): Response
    {
        $avis = $avisRepository->findAll();
        
        // Load ouvrier information for each avis
        $avisWithOuvriers = [];
        foreach ($avis as $avi) {
            $reservation = $avi->getReservation();
            $ouvrier = null;
            
            if ($reservation && $reservation->getOuvrierId()) {
                $ouvrier = $ouvrierRepository->find($reservation->getOuvrierId());
            }
            
            $avisWithOuvriers[] = [
                'avis' => $avi,
                'ouvrier' => $ouvrier,
            ];
        }
        
        return $this->render('avis/index.html.twig', [
            'avisWithOuvriers' => $avisWithOuvriers,
        ]);
    }

    #[Route('/new', name: 'app_avis_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository, AvisRepository $avisRepository): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();
        
        if (!$client) {
            $this->addFlash('error', 'Vous devez être un client pour laisser un avis.');
            return $this->redirectToRoute('app_home');
        }

        // Check if reservation ID is provided in query
        $reservationId = $request->query->get('reservation');
        $selectedReservation = null;
        
        if ($reservationId) {
            $selectedReservation = $reservationRepository->find($reservationId);
            if ($selectedReservation && $selectedReservation->getClient() === $client && 
                $selectedReservation->getStatut() === 'Terminée' && $selectedReservation->getOuvrierId()) {
                // Check if avis already exists
                $existingAvis = $avisRepository->findOneBy(['reservation' => $selectedReservation]);
                if ($existingAvis) {
                    $this->addFlash('warning', 'Vous avez déjà laissé un avis pour cette réservation.');
                    return $this->redirectToRoute('app_profile_index');
                }
            } else {
                $selectedReservation = null;
            }
        }

        // Get completed reservations with ouvrier assigned for this client
        $reservations = $reservationRepository->createQueryBuilder('r')
            ->where('r.client = :client')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.ouvrierId IS NOT NULL')
            ->setParameter('client', $client)
            ->setParameter('statut', 'Terminée')
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();

        // Filter out reservations that already have avis
        $availableReservations = [];
        foreach ($reservations as $reservation) {
            $existingAvis = $avisRepository->findOneBy(['reservation' => $reservation]);
            if (!$existingAvis) {
                $availableReservations[] = $reservation;
            }
        }

        $avi = new Avis();
        if ($selectedReservation) {
            $avi->setReservation($selectedReservation);
        }
        
        $form = $this->createForm(AvisType::class, $avi, [
            'reservations' => $availableReservations,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify the reservation belongs to the client
            $reservation = $avi->getReservation();
            if ($reservation->getClient() !== $client) {
                $this->addFlash('error', 'Vous ne pouvez pas évaluer cette réservation.');
                return $this->redirectToRoute('app_avis_new');
            }

            // Check if avis already exists for this reservation
            $existingAvis = $avisRepository->findOneBy(['reservation' => $reservation]);
            if ($existingAvis) {
                $this->addFlash('warning', 'Vous avez déjà laissé un avis pour cette réservation.');
                return $this->redirectToRoute('app_profile_index');
            }

            $entityManager->persist($avi);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été enregistré avec succès!');
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('avis/new.html.twig', [
            'avi' => $avi,
            'form' => $form,
            'reservations' => $availableReservations,
        ]);
    }

    #[Route('/{id}', name: 'app_avis_show', methods: ['GET'])]
    public function show(Avis $avi): Response
    {
        return $this->render('avis/show.html.twig', [
            'avi' => $avi,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_avis_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Avis $avi, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();
        
        if (!$client || $avi->getReservation()->getClient() !== $client) {
            throw $this->createAccessDeniedException();
        }

        $reservations = [$avi->getReservation()];
        
        $form = $this->createForm(AvisType::class, $avi, [
            'reservations' => $reservations,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Avis mis à jour avec succès!');
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('avis/edit.html.twig', [
            'avi' => $avi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_avis_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Avis $avi, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();
        
        if (!$client || $avi->getReservation()->getClient() !== $client) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$avi->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($avi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
    }
}
