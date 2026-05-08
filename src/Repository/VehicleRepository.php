<?php

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicle>
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    public function findAvailableVehicles(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.etat = :etat')
            ->setParameter('etat', 'disponible')
            ->orderBy('v.immatriculation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByEtat(): array
    {
        $results = $this->createQueryBuilder('v')
            ->select('v.etat, COUNT(v.id) as count')
            ->groupBy('v.etat')
            ->getQuery()
            ->getResult();
    
        $stats = [];
        foreach ($results as $result) {
            $stats[$result['etat']] = $result['count'];
        }
    
        return $stats;
    }

    //    /**
    //     * @return Vehicle[] Returns an array of Vehicle objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('v.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Vehicle
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
