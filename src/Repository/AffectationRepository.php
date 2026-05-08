<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Affectation>
 */
class AffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affectation::class);
    }

    /**
     * Vérifie si un véhicule a déjà une affectation sur une période donnée
     */
    public function hasOverlappingAffectation(
        Vehicle $vehicle,
        \DateTimeInterface $dateDebut,
        ?\DateTimeInterface $dateFin,
        ?int $excludeId = null
    ): bool {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.vehicle = :vehicle')
            ->setParameter('vehicle', $vehicle);
            
        // Si dateFin est null, on considère que c'est une affectation permanente
        // On utilise dateDebut comme date de fin pour la comparaison
        $endDate = $dateFin ?? $dateDebut;
        
        // Condition de chevauchement :
        // Une affectation existe si :
        // - Elle commence avant ou à la date de fin demandée
        // - Elle se termine après ou à la date de début demandée (ou n'a pas de date de fin)
        $qb->andWhere('a.dateDebut <= :endDate')
           ->andWhere('(a.dateFin >= :startDate OR a.dateFin IS NULL)')
           ->setParameter('startDate', $dateDebut)
           ->setParameter('endDate', $endDate);
        
        // Exclure l'affectation en cours d'édition
        if ($excludeId !== null) {
            $qb->andWhere('a.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }
        
        $count = (int) $qb->getQuery()->getSingleScalarResult();
        return $count > 0;
    }

    /**
     * Récupère les affectations actives (en cours)
     */
    public function findActiveAffectations(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('a')
            ->where('a.dateDebut <= :now')
            ->andWhere('(a.dateFin >= :now OR a.dateFin IS NULL)')
            ->setParameter('now', $now)
            ->leftJoin('a.vehicle', 'v')
            ->leftJoin('a.conducteur', 'u')
            ->addSelect('v', 'u')
            ->orderBy('a.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les affectations d'un véhicule spécifique
     */
    public function findByVehicle(Vehicle $vehicle): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.vehicle = :vehicle')
            ->setParameter('vehicle', $vehicle)
            ->orderBy('a.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les affectations d'un conducteur spécifique
     */
    public function findByConducteur($conducteur): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.conducteur = :conducteur')
            ->setParameter('conducteur', $conducteur)
            ->orderBy('a.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total d'affectations
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.vehicle', 'v')
            ->leftJoin('a.conducteur', 'u')
            ->addSelect('v', 'u')
            ->orderBy('a.dateDebut', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        }

    //    /**
    //     * @return Affectation[] Returns an array of Affectation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Affectation
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}