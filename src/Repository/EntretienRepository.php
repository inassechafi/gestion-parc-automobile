<?php

namespace App\Repository;

use App\Entity\Entretien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entretien>
 */
class EntretienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entretien::class);
    }

    public function getTotalCost(): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.cout) as total')
            ->getQuery()
            ->getSingleScalarResult();
    
        return $result ?? 0;
    }

    public function findByVehicle($vehicle, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.vehicle = :vehicle')
            ->setParameter('vehicle', $vehicle)
            ->orderBy('e.date', 'DESC');
    
        if ($limit) {
            $qb->setMaxResults($limit);
        }
    
        return $qb->getQuery()->getResult();
    }

    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.vehicle', 'v')
            ->addSelect('v')
            ->orderBy('e.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        }

    //    /**
    //     * @return Entretien[] Returns an array of Entretien objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Entretien
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
