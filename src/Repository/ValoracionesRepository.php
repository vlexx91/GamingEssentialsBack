<?php

namespace App\Repository;

use App\Entity\Valoraciones;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Valoraciones>
 */
class ValoracionesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Valoraciones::class);
    }

    //    /**
    //     * @return Valoraciones[] Returns an array of Valoraciones objects
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

    //    public function findOneBySomeField($value): ?Valoraciones
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('v')
            ->getQuery()
            ->getResult();
    }

    // src/Repository/ValoracionesRepository.php
    public function findByProducto(int $productoId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.producto = :productoId')
            ->andWhere('v.activado = true')
            ->setParameter('productoId', $productoId)
            ->getQuery()
            ->getResult();
    }
}
