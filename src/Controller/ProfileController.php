<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile', name: 'app_profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: '_index')]
    public function index(ReservationRepository $reservationRepository, AvisRepository $avisRepository): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();
        
        if (!$client) {
            $this->addFlash('error', 'Profil client non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        // Get all reservations for this client
        $reservations = $reservationRepository->findBy(
            ['client' => $client],
            ['date' => 'DESC', 'heure' => 'DESC']
        );

        // Get existing avis for reservations
        $avisByReservation = [];
        foreach ($reservations as $reservation) {
            $avis = $avisRepository->findOneBy(['reservation' => $reservation]);
            if ($avis) {
                $avisByReservation[$reservation->getId()] = $avis;
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'client' => $client,
            'reservations' => $reservations,
            'avisByReservation' => $avisByReservation,
        ]);
    }
}

