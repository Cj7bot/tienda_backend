<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCategoriaAndEstado(string $categoria, string $estado = 'disponible'): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.categoria = :categoria')
            ->andWhere('p.estado = :estado')
            ->setParameter('categoria', $categoria)
            ->setParameter('estado', $estado)
            ->orderBy('p.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLowStock(int $threshold = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stock <= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countOutOfStock(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id_producto)')
            ->where('p.stock <= :stock')
            ->setParameter('stock', 0)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
