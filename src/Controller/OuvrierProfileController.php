<?php

namespace App\Controller;

use App\Entity\Ouvrier;
use App\Form\OuvrierProfileType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ouvrier/profile', name: 'app_ouvrier_profile')]
#[IsGranted('ROLE_OVR')]
class OuvrierProfileController extends AbstractController
{
    #[Route('/', name: '_index')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $client = $user->getClient();
        $ouvrier = $user->getOuvrier();
        
        if (!$client) {
            $this->addFlash('error', 'Profil client non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        if (!$ouvrier) {
            // Create ouvrier if it doesn't exist
            $ouvrier = new Ouvrier();
            $ouvrier->setUser($user);
            $ouvrier->setNom($client->getNom());
            $ouvrier->setDisponible(true);
            $ouvrier->setZoneService('');
        }

        // Get all reservations for this ouvrier
        $reservations = $reservationRepository->findBy(
            ['ouvrierId' => $ouvrier->getId()],
            ['date' => 'DESC', 'heure' => 'DESC']
        );

        return $this->render('ouvrier/profile.html.twig', [
            'user' => $user,
            'client' => $client,
            'ouvrier' => $ouvrier,
            'reservations' => $reservations,
        ]);
    }

    #[Route('/edit', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $ouvrier = $user->getOuvrier();
        
        if (!$ouvrier) {
            // Create ouvrier if it doesn't exist
            $ouvrier = new Ouvrier();
            $ouvrier->setUser($user);
            $client = $user->getClient();
            if ($client) {
                $ouvrier->setNom($client->getNom());
            }
            $ouvrier->setDisponible(true);
            $ouvrier->setZoneService('');
        }

        $form = $this->createForm(OuvrierProfileType::class, $ouvrier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$ouvrier->getUser()) {
                $ouvrier->setUser($user);
            }
            $entityManager->persist($ouvrier);
            $entityManager->flush();

            $this->addFlash('success', 'Profil ouvrier mis à jour avec succès!');
            return $this->redirectToRoute('app_ouvrier_profile_index');
        }

        return $this->render('ouvrier/profile_edit.html.twig', [
            'ouvrier' => $ouvrier,
            'form' => $form,
        ]);
    }
}


