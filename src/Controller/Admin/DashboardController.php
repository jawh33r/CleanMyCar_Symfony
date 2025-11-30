<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Client;
use App\Entity\Ouvrier;
use App\Entity\Reservation;
use App\Entity\Avis;
use App\Entity\Admin;
use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use App\Repository\OuvrierRepository;
use App\Repository\ReservationRepository;
use App\Repository\AvisRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin/dashboard', routeName: 'app_admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserRepository $userRepository,
        private ClientRepository $clientRepository,
        private OuvrierRepository $ouvrierRepository,
        private ReservationRepository $reservationRepository,
        private AvisRepository $avisRepository
    ) {}

    public function index(): Response
    {
        // Get statistics
        $stats = $this->getStatistics();

        // Render custom dashboard with statistics
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }

    private function getStatistics(): array
    {
        $qb = $this->reservationRepository->createQueryBuilder('r');
        
        // Service type statistics
        $serviceTypes = [
            'Lavage extérieur à domicile',
            'Lavage intérieur complet',
            'Lavage complete'
        ];
        
        $serviceStats = [];
        foreach ($serviceTypes as $serviceType) {
            $serviceStats[$serviceType] = $this->reservationRepository->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.typeService = :type')
                ->setParameter('type', $serviceType)
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        // Revenue statistics
        $totalRevenue = $this->reservationRepository->createQueryBuilder('r')
            ->select('SUM(r.montant)')
            ->where('r.montant IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        $avgRevenue = $this->reservationRepository->createQueryBuilder('r')
            ->select('AVG(r.montant)')
            ->where('r.montant IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Average rating
        $avgRating = $this->avisRepository->createQueryBuilder('a')
            ->select('AVG(a.note)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // User role breakdown
        $allUsers = $this->userRepository->findAll();
        
        $roleCounts = [
            'ROLE_USER' => 0,
            'ROLE_ADMIN' => 0,
            'ROLE_OVR' => 0,
        ];
        
        foreach ($allUsers as $user) {
            $roles = $user->getRoles();
            foreach ($roles as $role) {
                if (isset($roleCounts[$role])) {
                    $roleCounts[$role]++;
                }
            }
        }
        
        // Recent reservations (last 5)
        $recentReservations = $this->reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.client', 'c')
            ->leftJoin('c.user', 'u')
            ->addSelect('c', 'u')
            ->orderBy('r.date', 'DESC')
            ->addOrderBy('r.heure', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        // Reservations by ouvrier
        $reservationsWithOuvrier = $this->reservationRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.ouvrierId IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'total_users' => $this->userRepository->count([]),
            'total_clients' => $this->clientRepository->count([]),
            'total_ouvriers' => $this->ouvrierRepository->count([]),
            'total_reservations' => $this->reservationRepository->count([]),
            'total_avis' => $this->avisRepository->count([]),
            'reservations_en_attente' => $this->reservationRepository->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.statut = :statut')
                ->setParameter('statut', 'En attente')
                ->getQuery()
                ->getSingleScalarResult(),
            'reservations_en_cours' => $this->reservationRepository->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.statut = :statut')
                ->setParameter('statut', 'En cours')
                ->getQuery()
                ->getSingleScalarResult(),
            'reservations_terminees' => $this->reservationRepository->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.statut = :statut')
                ->setParameter('statut', 'Terminée')
                ->getQuery()
                ->getSingleScalarResult(),
            'ouvriers_disponibles' => $this->ouvrierRepository->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.disponible = :disponible')
                ->setParameter('disponible', true)
                ->getQuery()
                ->getSingleScalarResult(),
            'ouvriers_indisponibles' => $this->ouvrierRepository->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.disponible = :disponible')
                ->setParameter('disponible', false)
                ->getQuery()
                ->getSingleScalarResult(),
            'service_stats' => $serviceStats,
            'total_revenue' => $totalRevenue,
            'avg_revenue' => $avgRevenue,
            'avg_rating' => round($avgRating, 2),
            'role_counts' => $roleCounts,
            'recent_reservations' => $recentReservations,
            'reservations_with_ouvrier' => $reservationsWithOuvrier,
            'reservations_without_ouvrier' => $this->reservationRepository->count([]) - $reservationsWithOuvrier,
        ];
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🚗 CleanMyCar Admin')
            ->setFaviconPath('favicon.ico')
            ->setTextDirection('ltr')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        // Users & Clients Section
        yield MenuItem::section('Users & Clients');
        yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Clients', 'fa fa-address-card', Client::class);
        yield MenuItem::linkToCrud('Admins', 'fa fa-user-shield', Admin::class);
        
        // Services Section
        yield MenuItem::section('Services');
        yield MenuItem::linkToCrud('Ouvriers', 'fa fa-tools', Ouvrier::class);
        yield MenuItem::linkToCrud('Reservations', 'fa fa-calendar-check', Reservation::class);
        yield MenuItem::linkToCrud('Avis', 'fa fa-star', Avis::class);
        
        // Quick Actions
        yield MenuItem::section('Quick Actions');
        yield MenuItem::linkToRoute('Website Frontend', 'fa fa-globe', 'app_home');
        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
    }
}
