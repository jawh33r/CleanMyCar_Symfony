<?php

namespace App\Controller;

use App\Entity\Ouvrier;
use App\Form\OuvrierType;
use App\Repository\OuvrierRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ouvrier')]
class OuvrierController extends AbstractController
{
    #[Route('/', name: 'app_ouvrier_index', methods: ['GET'])]
    public function index(OuvrierRepository $ouvrierRepository, ReservationRepository $reservationRepository): Response
    {
        $ouvriers = $ouvrierRepository->findAll();
        
        // Count reservations for each ouvrier
        $reservationCounts = [];
        foreach ($ouvriers as $ouvrier) {
            $count = $reservationRepository->count(['ouvrierId' => $ouvrier->getId()]);
            $reservationCounts[$ouvrier->getId()] = $count;
        }
        
        return $this->render('ouvrier/index.html.twig', [
            'ouvriers' => $ouvriers,
            'reservationCounts' => $reservationCounts,
        ]);
    }

    #[Route('/new', name: 'app_ouvrier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ouvrier = new Ouvrier();
        $form = $this->createForm(OuvrierType::class, $ouvrier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ouvrier);
            $entityManager->flush();

            return $this->redirectToRoute('app_ouvrier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ouvrier/new.html.twig', [
            'ouvrier' => $ouvrier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ouvrier_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Ouvrier $ouvrier, ReservationRepository $reservationRepository): Response
    {
        // Count reservations for this ouvrier
        $reservationCount = $reservationRepository->count(['ouvrierId' => $ouvrier->getId()]);
        
        // Get reservations for this ouvrier
        $reservations = $reservationRepository->findBy(
            ['ouvrierId' => $ouvrier->getId()],
            ['date' => 'DESC', 'heure' => 'DESC']
        );
        
        return $this->render('ouvrier/show.html.twig', [
            'ouvrier' => $ouvrier,
            'reservationCount' => $reservationCount,
            'reservations' => $reservations,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ouvrier_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Ouvrier $ouvrier, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OuvrierType::class, $ouvrier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_ouvrier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ouvrier/edit.html.twig', [
            'ouvrier' => $ouvrier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ouvrier_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Ouvrier $ouvrier, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ouvrier->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($ouvrier);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ouvrier_index', [], Response::HTTP_SEE_OTHER);
    }
}

