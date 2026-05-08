<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\VehicleRepository;
use App\Repository\AffectationRepository;
use App\Repository\EntretienRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Restriction d'accès : seuls les administrateurs peuvent voir le tableau de bord
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    /**
     * Page principale du tableau de bord
     * Route : GET /dashboard
     * 
     * Récupère et affiche toutes les statistiques importantes du système
     * pour donner une vue d'ensemble à l'administrateur
     */
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        VehicleRepository $vehicleRepo,          // Repository pour les véhicules
        AffectationRepository $affectationRepo,  // Repository pour les affectations
        EntretienRepository $entretienRepo       // Repository pour les entretiens
    ): Response {
       
        // STATISTIQUES OPTIMISÉES (sans charger tous les objets)
        
        // Nombre total de véhicules dans le système
        $totalVehicles = $vehicleRepo->count([]);
        
        // Nombre de véhicules actuellement disponibles (état = 'disponible')
        $availableVehicles = $vehicleRepo->count(['etat' => 'disponible']);
        
        // Nombre total d'affectations créées
        $totalAffectations = $affectationRepo->count([]);
        
        // Nombre d'affectations actuellement en cours (méthode personnalisée)
        $activeAffectations = count($affectationRepo->findActiveAffectations());
        
        // Nombre total d'opérations d'entretien effectuées
        $totalEntretiens = $entretienRepo->count([]);
        
        // Coût total cumulé de tous les entretiens (méthode personnalisée)
        $totalCoutEntretiens = $entretienRepo->getTotalCost();
        
        // DONNÉES RÉCENTES (limitées pour éviter la surcharge)
        
        // 5 dernières affectations créées
        $recentAffectations = $affectationRepo->findRecent(5);
        
        // 5 derniers entretiens effectués
        $recentEntretiens = $entretienRepo->findRecent(5);
        
        // STATISTIQUES DÉTAILLÉES
        
        // Répartition des véhicules par état (disponible, en maintenance, etc.)
        $vehicleStats = $vehicleRepo->countByEtat();
        
        // RENDU DE LA VUE AVEC TOUTES LES DONNÉES

        return $this->render('dashboard/index.html.twig', [
           
            // TOTAUX & CHIFFRES CLÉS
            'total_vehicles' => $totalVehicles,
            'available_vehicles' => $availableVehicles,
            'total_affectations' => $totalAffectations,
            'active_affectations' => $activeAffectations,
            'total_entretiens' => $totalEntretiens,
            'total_cout_entretiens' => $totalCoutEntretiens,
            
        
            // LISTES RÉCENTES
            'recent_affectations' => $recentAffectations,
            'recent_entretiens' => $recentEntretiens,
            
           
            // STATISTIQUES DÉTAILLÉES
            'vehicle_stats' => $vehicleStats,
            
           
            // INDICATEURS CALCULÉS
           
            // Taux de disponibilité en pourcentage
            'availability_rate' => $totalVehicles > 0 ? 
                round(($availableVehicles / $totalVehicles) * 100, 1) : 0,
                // Calcul : (véhicules disponibles / total véhicules) * 100
                // Arrondi à 1 décimale
                // Gestion du cas où il n'y a pas de véhicules (division par zéro)
        ]);
    }
}