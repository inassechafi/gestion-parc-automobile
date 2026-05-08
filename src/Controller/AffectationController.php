<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Form\AffectationType;
use App\Repository\AffectationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Définition de la route de base pour toutes les actions de ce contrôleur
#[Route('/affectation')]

// Restriction d'accès : seuls les utilisateurs avec le rôle ROLE_ADMIN peuvent accéder à ce contrôleur
#[IsGranted('ROLE_ADMIN')]
final class AffectationController extends AbstractController
{
    /**
     * Affiche la liste de toutes les affectations
     * Route : GET /affectation
     */
    #[Route(name: 'app_affectation_index', methods: ['GET'])]
    public function index(AffectationRepository $affectationRepository): Response
    {
        // Récupère toutes les affectations via le repository et les passe au template
        return $this->render('affectation/index.html.twig', [
            'affectations' => $affectationRepository->findAll(),
        ]);
    }

    /**
     * Crée une nouvelle affectation
     * Route : GET|POST /affectation/new
     * 
     * Processus :
     * 1. GET : Affiche le formulaire vide
     * 2. POST : Traite le formulaire soumis
     */
    #[Route('/new', name: 'app_affectation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        AffectationRepository $affectationRepository
    ): Response {
        // Crée une nouvelle instance d'Affectation
        $affectation = new Affectation();
        
        // Crée le formulaire lié à l'entité Affectation
        $form = $this->createForm(AffectationType::class, $affectation);
        $form->handleRequest($request); // Lie les données de la requête au formulaire

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // VÉRIFICATION DES CHEVAUCHEMENTS : s'assure qu'un véhicule n'est pas déjà affecté sur la même période
            if ($affectationRepository->hasOverlappingAffectation(
                $affectation->getVehicle(),        // Véhicule à affecter
                $affectation->getDateDebut(),      // Date de début de l'affectation
                $affectation->getDateFin()         // Date de fin de l'affectation
            )) {
                // Message d'erreur si chevauchement détecté
                $this->addFlash('error', 
                    'Ce véhicule est déjà affecté sur cette période.'
                );
            
                // Re-affiche le formulaire avec les données saisies et le message d'erreur
                return $this->render('affectation/new.html.twig', [
                    'affectation' => $affectation,
                    'form' => $form->createView(), // Important: createView() transforme le formulaire pour Twig
                ]);
            }
        
            // Persiste la nouvelle affectation en base de données
            $entityManager->persist($affectation);
            $entityManager->flush(); // Exécute les opérations en base

            // Message de succès
            $this->addFlash('success', 'Affectation créée avec succès.');
            
            // Redirige vers la liste des affectations
            return $this->redirectToRoute('app_affectation_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire (premier affichage GET ou formulaire invalide)
        return $this->render('affectation/new.html.twig', [
            'affectation' => $affectation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche les détails d'une affectation spécifique
     * Route : GET /affectation/{id}
     * 
     * Symfony convertit automatiquement l'id en objet Affectation via le param converter
     */
    #[Route('/{id}', name: 'app_affectation_show', methods: ['GET'])]
    public function show(Affectation $affectation): Response
    {
        // Passe l'objet affectation au template d'affichage
        return $this->render('affectation/show.html.twig', [
            'affectation' => $affectation,
        ]);
    }

    /**
     * Modifie une affectation existante
     * Route : GET|POST /affectation/{id}/edit
     * 
     * Similaire à new() mais avec une affectation existante
     */
    #[Route('/{id}/edit', name: 'app_affectation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Affectation $affectation,  // L'affectation à modifier (récupérée par son id)
        EntityManagerInterface $entityManager,
        AffectationRepository $affectationRepository
    ): Response {
        // Crée le formulaire pré-rempli avec l'affectation existante
        $form = $this->createForm(AffectationType::class, $affectation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // VÉRIFICATION DES CHEVAUCHEMENTS (exclure l'actuelle)
            // Le 4ème paramètre ($affectation->getId()) exclut cette affectation de la vérification
            if ($affectationRepository->hasOverlappingAffectation(
                $affectation->getVehicle(),
                $affectation->getDateDebut(),
                $affectation->getDateFin(),
                $affectation->getId() // Exclure cette affectation pour éviter un conflit avec elle-même
            )) {
                $this->addFlash('error', 
                    'Ce véhicule est déjà affecté sur cette période.'
                );
            
                return $this->render('affectation/edit.html.twig', [
                    'affectation' => $affectation,
                    'form' => $form->createView(),
                ]);
            }
        
            // Met à jour l'affectation en base de données (flush sans persist car l'objet est déjà géré)
            $entityManager->flush();
            
            $this->addFlash('success', 'Affectation modifiée avec succès.');
            return $this->redirectToRoute('app_affectation_index', [], Response::HTTP_SEE_OTHER);
        }   

        // Affiche le formulaire de modification
        return $this->render('affectation/edit.html.twig', [
            'affectation' => $affectation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche la page de confirmation de suppression
     * Route : GET /affectation/{id}/delete-confirm
     * 
     * Bonne pratique : Séparer la confirmation de l'action de suppression
     */
    #[Route('/{id}/delete-confirm', name: 'app_affectation_delete_confirm', methods: ['GET'])]
    public function confirmDelete(Affectation $affectation): Response
    {
        // Affiche un template de confirmation (généralement une modale ou page dédiée)
        return $this->render('affectation/_delete_form.html.twig', [
            'affectation' => $affectation,
        ]);
    }

    /**
     * Supprime une affectation
     * Route : POST /affectation/{id}
     * 
     * L'action de suppression nécessite une requête POST pour des raisons de sécurité
     */
    #[Route('/{id}', name: 'app_affectation_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Affectation $affectation, 
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifie le token CSRF pour prévenir les attaques
        if ($this->isCsrfTokenValid('delete'.$affectation->getId(), 
            $request->getPayload()->getString('_token'))) {
        
            // Vérifie si l'affectation est active (règle métier)
            // UTILISER LA MÉTHODE isActive() de l'entité Affectation
            if ($affectation->isActive()) {
                $this->addFlash('error', 
                    'Impossible de supprimer une affectation en cours.'
                );
            } else {
                // Supprime l'affectation de la base de données
                $entityManager->remove($affectation);
                $entityManager->flush();
                $this->addFlash('success', 'Affectation supprimée avec succès.');
            }
        }

        // Redirige vers la liste des affectations
        return $this->redirectToRoute('app_affectation_index', [], Response::HTTP_SEE_OTHER);
    }
}