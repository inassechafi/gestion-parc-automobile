<?php

namespace App\Controller;

use App\Entity\Entretien;
use App\Form\EntretienType;
use App\Repository\EntretienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Définition du préfixe de route pour toutes les actions de ce contrôleur
#[Route('/entretien')]
// Restriction d'accès : seuls les administrateurs peuvent gérer les entretiens
#[IsGranted('ROLE_ADMIN')]
final class EntretienController extends AbstractController
{
    /**
     * Affiche la liste de tous les entretiens
     * Route : GET /entretien
     */
    #[Route(name: 'app_entretien_index', methods: ['GET'])]
    public function index(EntretienRepository $entretienRepository): Response
    {
        // Récupère tous les entretiens et les passe au template
        return $this->render('entretien/index.html.twig', [
            'entretiens' => $entretienRepository->findAll(),
        ]);
    }

    /**
     * Crée un nouvel entretien
     * Route : GET|POST /entretien/new
     */
    #[Route('/new', name: 'app_entretien_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Crée une nouvelle instance d'Entretien
        $entretien = new Entretien();
        
        // Crée le formulaire lié à l'entité Entretien
        $form = $this->createForm(EntretienType::class, $entretien);
        $form->handleRequest($request); // Lie les données de la requête au formulaire

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Persiste le nouvel entretien en base de données
            $entityManager->persist($entretien);
            $entityManager->flush(); // Exécute l'insertion

            // Message de succès
            $this->addFlash('success', 'Entretien créé avec succès.');
            
            // Redirige vers la liste des entretiens
            return $this->redirectToRoute('app_entretien_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire de création (premier affichage ou formulaire invalide)
        return $this->render('entretien/new.html.twig', [
            'entretien' => $entretien,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche les détails d'un entretien spécifique
     * Route : GET /entretien/{id}
     */
    #[Route('/{id}', name: 'app_entretien_show', methods: ['GET'])]
    public function show(Entretien $entretien): Response
    {
        // Passe l'entretien au template d'affichage détaillé
        return $this->render('entretien/show.html.twig', [
            'entretien' => $entretien,
        ]);
    }

    /**
     * Modifie un entretien existant
     * Route : GET|POST /entretien/{id}/edit
     */
    #[Route('/{id}/edit', name: 'app_entretien_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Entretien $entretien, EntityManagerInterface $entityManager): Response
    {
        // Crée le formulaire pré-rempli avec l'entretien existant
        $form = $this->createForm(EntretienType::class, $entretien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Met à jour l'entretien en base de données
            // Pas besoin de persist() car l'entretien est déjà "managed" par Doctrine
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Entretien modifié avec succès.');
            
            // Redirige vers la liste des entretiens
            return $this->redirectToRoute('app_entretien_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire de modification
        return $this->render('entretien/edit.html.twig', [
            'entretien' => $entretien,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche la page de confirmation de suppression
     * Route : GET /entretien/{id}/delete-confirm
     * 
     * Bonne pratique : séparer la confirmation de l'action de suppression
     * Cela permet d'avoir une interface utilisateur plus claire
     */
    #[Route('/{id}/delete-confirm', name: 'app_entretien_delete_confirm', methods: ['GET'])]
    public function confirmDelete(Entretien $entretien): Response
    {
        // Affiche un template de confirmation (généralement une modale)
        return $this->render('entretien/_delete_form.html.twig', [
            'entretien' => $entretien,
        ]);
    }

    /**
     * Supprime un entretien
     * Route : POST /entretien/{id}
     * 
     * L'action de suppression nécessite une requête POST pour des raisons de sécurité
     * Cela empêche la suppression accidentelle via un simple lien
     */
    #[Route('/{id}', name: 'app_entretien_delete', methods: ['POST'])]
    public function delete(Request $request, Entretien $entretien, EntityManagerInterface $entityManager): Response
    {
        // Vérifie le token CSRF pour prévenir les attaques
        // Le token est généré dans le formulaire de confirmation
        if ($this->isCsrfTokenValid('delete'.$entretien->getId(), $request->getPayload()->getString('_token'))) {
            // Supprime l'entretien de la base de données
            $entityManager->remove($entretien);
            $entityManager->flush(); // Exécute la suppression

            // Message de succès
            $this->addFlash('success', 'Entretien supprimé avec succès.');
            
            // Redirige vers la liste des entretiens
            return $this->redirectToRoute('app_entretien_index', [], Response::HTTP_SEE_OTHER);
        }

        // Si le token CSRF est invalide, redirige simplement vers la liste
        // (sans supprimer l'entretien pour des raisons de sécurité)
        return $this->redirectToRoute('app_entretien_index', [], Response::HTTP_SEE_OTHER);
    }
}