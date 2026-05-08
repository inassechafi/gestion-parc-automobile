<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\Entretien;
use App\Entity\Affectation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        // Utilisateurs normaux (ROLE_USER)
        $conducteurs = [];
        for ($i = 1; $i <= 8; $i++) {
            $conducteur = new User();
            $conducteur->setEmail("conducteur{$i}@parcauto.com");
            $conducteur->setNom($faker->lastName());
            $conducteur->setPrenom($faker->firstName());
            $conducteur->setRoles(['ROLE_USER']); 
            $conducteur->setPassword($this->passwordHasher->hashPassword($conducteur, 'user123'));
            $manager->persist($conducteur);
            $conducteurs[] = $conducteur;
        }
        
        $manager->flush();

        // Modèles de voitures 
        $modeles = [
            'Peugeot' => ['208', '308', '3008', '5008', 'Partner'],
            'Renault' => ['Clio', 'Megane', 'Kadjar', 'Captur', 'Kangoo'],
            'Citroën' => ['C3', 'C4', 'C5 Aircross', 'Berlingo'],
            'Dacia' => ['Sandero', 'Duster', 'Logan', 'Jogger'],
            'Volkswagen' => ['Golf', 'Polo', 'Tiguan', 'Passat', 'Caddy'],
        ];
        // États possibles
        $etats = [
            'disponible', 
            'en service', 
            'en panne', 
            'en entretien', 
            'hors service'
        ];

        $vehicules = [];
        $immatriculations = [];

        // Créer 15 véhicules
        for ($i = 1; $i <= 15; $i++) {

            // Choisir une marque aléatoire
            $marque = $faker->randomElement(array_keys($modeles));
            $modeleNom = $faker->randomElement($modeles[$marque]);
            $modeleComplet = "{$marque} {$modeleNom}";

            // Générer une immatriculation unique
            do {
                $immat = sprintf(
                    '%s-%03d-%s',
                    $faker->regexify('[A-Z]{2}'),
                    $faker->numberBetween(100, 999),
                    $faker->regexify('[A-Z]{2}')
                );
            } while (in_array($immat, $immatriculations));

            $immatriculations[] = $immat;

            $vehicule = new Vehicle();
            $vehicule->setImmatriculation($immat);
            $vehicule->setModele($modeleComplet);
            $vehicule->setEtat($faker->randomElement($etats));
            $vehicule->setKilometrage($faker->numberBetween(1000, 150000));

            $manager->persist($vehicule);
            $vehicules[] = $vehicule;
        }
        
        $manager->flush();

        // Créer des entretiens
        $typesEntretien = [
            'Révision', 
            'Réparation', 
            'Entretien', 
            'Contrôle technique', 
            'Vidange', 
            'Autre'
        ];

        $entretienCount = 0;

        // Pour chaque véhicule, créer 1-3 entretiens
        foreach ($vehicules as $vehicule) {
            $nbEntretiens = $faker->numberBetween(1, 3);
            
            for ($j = 0; $j < $nbEntretiens; $j++) {
                $entretien = new Entretien();
                $entretien->setDate($faker->dateTimeBetween('-1 year', 'now'));
                $entretien->setType($faker->randomElement($typesEntretien));

                $cout = match($entretien->getType()) {
                    'Révision' => $faker->randomFloat(2, 200, 600),
                    'Contrôle technique' => $faker->randomFloat(2, 70, 120),
                    'Vidange' => $faker->randomFloat(2, 80, 200),
                    'Réparation' => $faker->randomFloat(2, 300, 1500),
                    default => $faker->randomFloat(2, 50, 500)
                };
                $entretien->setCout($cout);

                $description = match($entretien->getType()) {
                    'Révision' => $faker->randomElement([
                        'Révision complète selon le plan d\'entretien constructeur.',
                        'Changement des filtres (air, huile, habitacle) et vidange moteur.',
                        'Révision périodique avec vérification des niveaux et des freins.',
                        'Entretien programmé : remplacement bougies et liquide de refroidissement.'
                    ]),
                    'Contrôle technique' => $faker->randomElement([
                        'Contrôle technique réglementaire effectué.',
                        'Visite technique avec contre-visite pour freins.',
                        'Contrôle technique valide jusqu\'à ' . $faker->dateTimeBetween('+1 year', '+2 years')->format('d/m/Y') . '.',
                        'Passage au centre de contrôle technique agréé.'
                    ]),
                    'Vidange' => $faker->randomElement([
                        'Vidange d\'huile moteur et remplacement du filtre à huile.',
                        'Vidange complète avec huile synthétique 5W30.',
                        'Remplacement huile moteur et filtres associés.',
                        'Vidange selon kilométrage préconisé par le constructeur.'
                    ]),
                    'Réparation' => $faker->randomElement([
                        'Remplacement des plaquettes de frein avant et arrière.',
                        'Changement des 4 pneus (pneus été Michelin).',
                        'Réparation système de climatisation : recharge gaz.',
                        'Remplacement batterie 12V 70Ah.',
                        'Réparation échappement : changement silencieux arrière.',
                        'Remplacement courroie de distribution et pompe à eau.'
                    ]),
                    'Entretien' => $faker->randomElement([
                        'Nettoyage et graissage des éléments de suspension.',
                        'Vérification et réglage des phares.',
                        'Lavage intégral et traitement anti-rouille.',
                        'Révision complète des éléments de sécurité.'
                    ]),
                    default => $faker->text(200)
                };
                $entretien->setDescription($description);

                $entretien->setVehicle($vehicule);
                
                $manager->persist($entretien);
                $entretienCount++;
            }
        }

        $manager->flush();

        // Créer des affectations
        $statsAffectations = [
            'à venir' => 0, 
            'en cours' => 0, 
            'terminées' => 0
        ];

        // 1. AFFECTATIONS TERMINÉES (6)
        for ($i = 0; $i < 6; $i++) {
            $affectation = new Affectation();
            
            // Choisir un véhicule
            $vehicule = $faker->randomElement($vehicules);
            $affectation->setVehicle($vehicule);
            $affectation->setConducteur($faker->randomElement($conducteurs));
            
            // Dates passées
            $dateDebut = $faker->dateTimeBetween('-6 months', '-1 month');
            $affectation->setDateDebut($dateDebut);
            
            $dateFin = $faker->dateTimeBetween(
                (clone $dateDebut)->modify('+1 week'),
                (clone $dateDebut)->modify('+3 weeks')
            );
            $affectation->setDateFin($dateFin);
            
            $manager->persist($affectation);
            $statsAffectations['terminées']++;
        }

        /// 2. AFFECTATIONS EN COURS (4)
        for ($i = 0; $i < 4; $i++) {
            $affectation = new Affectation();
            
            // Choisir un véhicule disponible si possible
            $vehiculesDisponibles = array_filter($vehicules, function($v) {
                return $v->isAvailable();
            });
            
            if (!empty($vehiculesDisponibles)) {
                $vehicule = $faker->randomElement($vehiculesDisponibles);
            } else {
                $vehicule = $faker->randomElement($vehicules);
            }
            
            $affectation->setVehicle($vehicule);
            $affectation->setConducteur($faker->randomElement($conducteurs));
            
            // Date de début passée, pas de date de fin
            $affectation->setDateDebut($faker->dateTimeBetween('-2 weeks', '-1 day'));
            $affectation->setDateFin(null); // NULL = en cours
            
            // Mettre à jour l'état du véhicule
            $vehicule->setEtat('en service');
            
            $manager->persist($affectation);
            $statsAffectations['en cours']++;
        }


        // 3. AFFECTATIONS À VENIR (3)
        for ($i = 0; $i < 3; $i++) {
            $affectation = new Affectation();
            
            // Choisir un véhicule
            $vehicule = $faker->randomElement($vehicules);
            $affectation->setVehicle($vehicule);
            $affectation->setConducteur($faker->randomElement($conducteurs));
            
            // Dates futures
            $dateDebut = $faker->dateTimeBetween('+3 days', '+2 weeks');
            $affectation->setDateDebut($dateDebut);
            
            $affectation->setDateFin($faker->dateTimeBetween(
                (clone $dateDebut)->modify('+1 week'),
                (clone $dateDebut)->modify('+3 weeks')
            ));
            
            $manager->persist($affectation);
            $statsAffectations['à venir']++;
        }

        $manager->flush();
    }
}
