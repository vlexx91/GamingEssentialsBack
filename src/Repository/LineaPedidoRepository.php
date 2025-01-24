<?php

namespace App\Repository;

use App\Entity\LineaPedido;
use App\Entity\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LineaPedido>
 */
class LineaPedidoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LineaPedido::class);
    }

    //    /**
    //     * @return LineaPedido[] Returns an array of LineaPedido objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?LineaPedido
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('l')
            ->getQuery()
            ->getResult();
    }

    // Método para obtener las líneas de pedido por producto
    public function findByProducto(Producto $producto)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.producto = :producto')
            ->setParameter('producto', $producto)
            ->getQuery()
            ->getResult();
    }
}
