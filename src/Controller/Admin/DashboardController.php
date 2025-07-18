<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Order;
use App\Entity\Product;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserRepository $userRepository,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private ChartBuilderInterface $chartBuilder
    ) {
    }

    #[Route('/dashboard', name: 'admin')]
    public function index(): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        // Obtener el rango de fechas seleccionado
        $range = $request->query->get('range', '30d');
        $rangeLabel = match($range) {
            '7d' => 'Últimos 7 días',
            '30d' => 'Últimos 30 días',
            'month' => 'Este mes',
            default => 'Últimos 30 días'
        };

        // Estadísticas de órdenes
        $newOrders = $this->orderRepository->count(['estado' => Order::STATUS_PENDING]);
        $onHoldOrders = $this->orderRepository->count(['estado' => Order::STATUS_PROCESSING]);
        $totalProducts = $this->productRepository->count([]);

        // Obtener datos de ventas según el rango seleccionado
        $salesData = match($range) {
            '7d' => $this->orderRepository->getSalesDataLast7Days(),
            '30d' => $this->orderRepository->getSalesDataLast30Days(),
            'month' => $this->orderRepository->getSalesDataThisMonth(),
            default => $this->orderRepository->getSalesDataLast30Days()
        };

        $labels = array_keys($salesData);
        $values = array_values($salesData);

        // Crear gráfico de ventas
        $salesChart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $salesChart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ventas Totales',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                    'data' => $values,
                    'fill' => true
                ]
            ]
        ]);

        $salesChart->setOptions([
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0,0,0,0.1)'
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => false
                ]
            ]
        ]);

        // Estadísticas adicionales
        $totalOrders = $this->orderRepository->count([]);
        $newCustomers = $this->userRepository->countNewCustomersLast7Days();

        // Calcular porcentajes de cambio
        $ordersLastWeek = $this->orderRepository->countOrdersLastWeek();
        $ordersWeekBefore = $this->orderRepository->countOrdersWeekBeforeLast();
        $orderChangePercent = $ordersWeekBefore > 0
            ? round((($ordersLastWeek - $ordersWeekBefore) / $ordersWeekBefore) * 100, 1)
            : 0;

        return $this->render('admin/dashboard.html.twig', [
            'new_orders' => $newOrders,
            'on_hold_orders' => $onHoldOrders,
            'out_of_stock_products' => $totalProducts,
            'sales_chart' => $salesChart,
            'total_orders' => $totalOrders,
            'new_customers' => $newCustomers,
            'order_change_percent' => $orderChangePercent,
            'range' => $range,
            'range_label' => $rangeLabel
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Pure Inka Foods')
            ->setLocales(['es']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Usuarios', 'fa fa-user', User::class);
        yield MenuItem::linkToCrud('Órdenes', 'fa fa-shopping-cart', Order::class);
        yield MenuItem::linkToCrud('Productos', 'fa fa-box', Product::class);
    }
}
