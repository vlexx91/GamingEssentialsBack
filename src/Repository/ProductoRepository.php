<?php

namespace App\Repository;

use App\Entity\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Producto>
 */
class ProductoRepository extends ServiceEntityRepository
{

    private $randomFunction;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Producto::class);
    }

//    /**
//     * @return Producto[] Returns an array of Producto objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }


    public function findAll(): array
    {
        return $this->createQueryBuilder('p')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.disponibilidad = :val')
            ->setParameter('val', true)
            ->getQuery()
            ->getResult();
    }

    public function findById(int $id): ?Producto
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCriteria(array $criterios)
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.disponibilidad = :val')
            ->setParameter('val', true);

        if (!empty($criterios['nombre'])) {
            $qb->andWhere('LOWER(p.nombre) LIKE LOWER(:nombre)')
                ->setParameter('nombre', '%' . strtolower($criterios['nombre']) . '%');
        }

        if (!empty($criterios['plataforma'])) {
            $qb->andWhere('p.plataforma = :plataforma')
                ->setParameter('plataforma', $criterios['plataforma']);
        }

        if (!empty($criterios['categoria'])) {
            $qb->andWhere('p.categoria = :categoria')
                ->setParameter('categoria', $criterios['categoria']);
        }

        if (!empty($criterios['minPrecio'])) {
            $qb->andWhere('p.precio >= :minPrecio')
                ->setParameter('minPrecio', $criterios['minPrecio']);
        }

        if (!empty($criterios['maxPrecio'])) {
            $qb->andWhere('p.precio <= :maxPrecio')
                ->setParameter('maxPrecio', $criterios['maxPrecio']);
        }

        return $qb->getQuery()->getResult();
    }

    public function findTop5MasVendidos()
    {
        return $this->createQueryBuilder('p')
            ->select('p.id, p.nombre, SUM(lp.cantidad) as total_vendidos')
            ->join('App\Entity\LineaPedido', 'lp', 'WITH', 'lp.producto = p.id')
            ->groupBy('p.id')
            ->orderBy('total_vendidos', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

}
