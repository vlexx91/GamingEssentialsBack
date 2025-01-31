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
            $qb->andWhere('UPPER(p.nombre) LIKE :nombre or LOWER(p.nombre) LIKE :nombre')
                ->setParameter('nombre', '%' . $criterios['nombre'] . '%');
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

    public function findRandomProducts(int $limit = 10): array
    {
        // Utiliza la conexión de Doctrine para ejecutar una consulta SQL directa
        $connection = $this->getEntityManager()->getConnection();

        // Realiza la consulta SQL directamente
        $sql = 'SELECT * FROM gaming_essentials.producto ORDER BY RANDOM() LIMIT :limit';

        // Prepara y ejecuta la consulta
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);

        // Obtén el resultado de la consulta
        return $stmt->executeQuery()->fetchAllAssociative();
    }


//    public function findRandomProducts(int $limit = 10)
//    {
//        return $this->createQueryBuilder('p')
//            ->orderBy('p.id', 'ASC')  // Ordena por el campo 'id' de forma ascendente
//            ->setMaxResults($limit)   // Limita el número de resultados a $limit
//            ->getQuery()
//            ->getResult();
//    }



}
