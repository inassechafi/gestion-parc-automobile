<?php

namespace App\Controller;

use App\Repository\AffectationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Restriction d'accès : tous les utilisateurs connectés peuvent voir leurs affectations
// ROLE_USER est généralement attribué à tous les utilisateurs authentifiés
#[IsGranted('ROLE_USER')]
class MesAffectationsController extends AbstractController
{
    /**
     * Affiche les affectations de l'utilisateur connecté
     * Route : GET /mes-affectations
     * 
     * Cette méthode permet à un conducteur de voir toutes ses affectations,
     * classées par statut (En cours, À venir, Terminé)
     */
    #[Route('/mes-affectations', name: 'app_mes_affectations')]
    public function index(AffectationRepository $affectationRepository): Response
    {
        // Récupère l'utilisateur actuellement connecté
        $user = $this->getUser();

        // Récupérer toutes les affectations du conducteur (utilisateur connecté)
        // Triées par date de début décroissante (les plus récentes en premier)
        $affectations = $affectationRepository->findBy(
            ['conducteur' => $user],  // Critère : seulement les affectations de cet utilisateur
            ['dateDebut' => 'DESC']   // Tri : par date de début descendante
        );

        // TRI DES AFFECTATIONS PAR STATUT
        
        // Initialiser les tableaux pour les 3 états possibles
        $affectationsEnCours = [];    // Affectations actuellement en cours
        $affectationsAVenir = [];     // Affectations programmées pour le futur
        $affectationsTerminees = [];  // Affectations déjà terminées

        // Parcourir toutes les affectations et les classer selon leur statut
        foreach ($affectations as $affectation) {
            // Récupère le statut de l'affectation via la méthode getStatut()
            // Le statut est déterminé par la logique métier de l'entité Affectation
            $statut = $affectation->getStatut();
            
            // Distribuer l'affectation dans le tableau correspondant selon son statut
            switch ($statut) {
                case 'En cours':
                    $affectationsEnCours[] = $affectation;
                    break;
                case 'À venir':
                    $affectationsAVenir[] = $affectation;
                    break;
                case 'Terminé':
                    $affectationsTerminees[] = $affectation;
                    break;
            }
        }

        // RENDU DE LA VUE AVEC LES DONNÉES ORGANISÉES

        return $this->render('mes_affectations/index.html.twig', [
            // Affectations classées par statut
            'affectations_en_cours' => $affectationsEnCours,
            'affectations_a_venir' => $affectationsAVenir,
            'affectations_terminees' => $affectationsTerminees,
            
            // Variable utile pour afficher/masquer des sections dans le template
            // Exemple : afficher un message "Aucune affectation" si l'utilisateur n'en a pas
            'has_affectations' => !empty($affectations),
        ]);
    }
}