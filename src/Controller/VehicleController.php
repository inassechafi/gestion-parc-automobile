<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Définition du préfixe de route pour toutes les actions de ce contrôleur
#[Route('/vehicle')]
// Restriction d'accès : seuls les administrateurs peuvent gérer les véhicules
#[IsGranted('ROLE_ADMIN')]
final class VehicleController extends AbstractController
{
    /**
     * Affiche la liste de tous les véhicules
     * Route : GET /vehicle
     */
    #[Route(name: 'app_vehicle_index', methods: ['GET'])]
    public function index(VehicleRepository $vehicleRepository): Response
    {
        // Récupère tous les véhicules et les passe au template
        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicleRepository->findAll(),
        ]);
    }

    /**
     * Crée un nouveau véhicule
     * Route : GET|POST /vehicle/new
     */
    #[Route('/new', name: 'app_vehicle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Crée une nouvelle instance de Vehicle
        $vehicle = new Vehicle();
        
        // Crée le formulaire lié à l'entité Vehicle
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persiste le nouveau véhicule en base de données
            $entityManager->persist($vehicle);
            $entityManager->flush();

            $this->addFlash('success', 'Véhicule créé avec succès.');
            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/new.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche les détails d'un véhicule spécifique
     * Route : GET /vehicle/{id}
     */
    #[Route('/{id}', name: 'app_vehicle_show', methods: ['GET'])]
    public function show(Vehicle $vehicle): Response
    {
        // Passe le véhicule au template d'affichage détaillé
        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Modifie un véhicule existant
     * Route : GET|POST /vehicle/{id}/edit
     */
    #[Route('/{id}/edit', name: 'app_vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        // Crée le formulaire pré-rempli avec le véhicule existant
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Met à jour le véhicule en base de données
            $entityManager->flush();

            $this->addFlash('success', 'Véhicule modifié avec succès.');
            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/edit.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche la page de confirmation de suppression
     * Route : GET /vehicle/{id}/delete-confirm
     */
    #[Route('/{id}/delete-confirm', name: 'app_vehicle_delete_confirm', methods: ['GET'])]
    public function confirmDelete(Vehicle $vehicle): Response
    {
        // Affiche un template de confirmation pour la suppression
        return $this->render('vehicle/_delete_form.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Supprime un véhicule avec vérifications des contraintes
     * Route : POST /vehicle/{id}
     * 
     * Cette méthode effectue plusieurs vérifications avant de permettre la suppression :
     * 1. Vérifie le token CSRF pour la sécurité
     * 2. Vérifie si le véhicule a des affectations actives
     * 3. Vérifie si le véhicule a des entretiens associés
     * 4. Donne des recommandations appropriées
     */
    #[Route('/{id}', name: 'app_vehicle_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        // Vérifie le token CSRF pour prévenir les attaques
        if ($this->isCsrfTokenValid('delete'.$vehicle->getId(), 
            $request->getPayload()->getString('_token'))) {
            
            // VÉRIFICATION 1 : AFFECTATIONS ACTIVES

            // Parcourt toutes les affectations du véhicule
            $hasActiveAffectations = false;
            foreach ($vehicle->getAffectations() as $affectation) {
                // Utilise la méthode isActive() de l'entité Affectation
                // pour déterminer si l'affectation est en cours
                if ($affectation->isActive()) {
                    $hasActiveAffectations = true;
                    break; // Sort de la boucle dès qu'une affectation active est trouvée
                }
            }

            // VÉRIFICATION 2 : ENTRETIENS ASSOCIÉS

            // Compte le nombre d'entretiens liés à ce véhicule
            $hasEntretiens = $vehicle->getEntretiens()->count() > 0;
            
            // CONSTRUCTION DES MESSAGES D'ERREUR

            // Tableau pour collecter les raisons qui empêchent la suppression
            $errorMessages = [];
            
            if ($hasActiveAffectations) {
                $errorMessages[] = 'affectations actives';
            }
            
            if ($hasEntretiens) {
                $errorMessages[] = 'entretiens associés';
            }
            
            // LOGIQUE DE DÉCISION : SUPPRIMER OU NON ?
            
            // CAS 1 : Contraintes bloquantes (affectations actives OU entretiens)
            if (!empty($errorMessages)) {
                // Le véhicule ne peut pas être supprimé
                $this->addFlash('error', 
                    sprintf(
                        'Impossible de supprimer ce véhicule car il a %s. ' .
                        'Il est recommandé de le marquer comme "hors service" plutôt que de le supprimer.',
                        implode(' et ', $errorMessages) // Combine les messages
                    )
                );
            } 
            // CAS 2 : Affectations historiques seulement (non actives)
            elseif ($vehicle->getAffectations()->count() > 0) {
                // Le véhicule peut techniquement être supprimé mais ce n'est pas recommandé
                // car il a un historique qu'il serait bon de conserver
                $this->addFlash('warning', 
                    'Ce véhicule a un historique d\'affectations. ' .
                    'Il est recommandé de le marquer comme "hors service" plutôt que de le supprimer.'
                );
            }
            // CAS 3 : Aucune contrainte - suppression possible
            else {
                // Supprime le véhicule de la base de données
                $entityManager->remove($vehicle);
                $entityManager->flush();
                $this->addFlash('success', 'Véhicule supprimé avec succès.');
            }
        } else {
            // CAS 4 : Token CSRF invalide
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        // Redirige toujours vers la liste des véhicules
        return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
    }
}