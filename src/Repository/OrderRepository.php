<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function generateSalesData(\DateTime $startDate, \DateTime $endDate): array
    {
        $salesData = [];
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);

        // Base inicial para las ventas y factor de crecimiento
        $baseValue = 2;
        $growthFactor = 1.15; // 15% de crecimiento promedio
        $randomVariation = 0.3; // 30% de variación aleatoria

        foreach ($period as $date) {
            // Calcular valor base con crecimiento
            $daysSinceStart = $startDate->diff($date)->days;
            $expectedValue = $baseValue * pow($growthFactor, $daysSinceStart / 7); // Crecimiento semanal

            // Añadir variación aleatoria
            $variation = $expectedValue * $randomVariation;
            $finalValue = $expectedValue + (rand(-100, 100) / 100) * $variation;

            // Asegurar que el valor no sea menor que el base
            $finalValue = max($baseValue, $finalValue);

            // Redondear a entero
            $salesData[$date->format('d M')] = round($finalValue);
        }

        return $salesData;
    }

    public function getSalesDataLast7Days(): array
    {
        $sevenDaysAgo = new \DateTime('-7 days');
        $today = new \DateTime();
        return $this->generateSalesData($sevenDaysAgo, $today);
    }

    public function getSalesDataLast30Days(): array
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        $today = new \DateTime();
        return $this->generateSalesData($thirtyDaysAgo, $today);
    }

    public function getSalesDataThisMonth(): array
    {
        $firstDayOfMonth = new \DateTime('first day of this month');
        $today = new \DateTime();
        return $this->generateSalesData($firstDayOfMonth, $today);
    }

    public function countOrdersLastWeek(): int
    {
        $lastWeekStart = new \DateTime('-7 days');

        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.orderDate >= :lastWeekStart')
            ->andWhere('o.estado = :estado')
            ->setParameter('lastWeekStart', $lastWeekStart)
            ->setParameter('estado', Order::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOrdersWeekBeforeLast(): int
    {
        $twoWeeksAgo = new \DateTime('-14 days');
        $oneWeekAgo = new \DateTime('-7 days');

        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.orderDate >= :twoWeeksAgo')
            ->andWhere('o.orderDate < :oneWeekAgo')
            ->andWhere('o.estado = :estado')
            ->setParameter('twoWeeksAgo', $twoWeeksAgo)
            ->setParameter('oneWeekAgo', $oneWeekAgo)
            ->setParameter('estado', Order::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
