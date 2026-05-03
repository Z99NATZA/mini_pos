<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Transport\HTTP\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Provides aggregated statistics for the dashboard overview.
 */
class DashboardController
{
    /**
     * GET /api/dashboard
     * Returns today's sales totals, order counts, product count, and a 7-day sales chart.
     *
     * @param array<string, mixed> $authUser
     */
    public function index(Request $request, array $authUser = []): SymfonyResponse
    {
        try {
            $pdo = Connection::getInstance();

            // Today's total sales amount.
            $todaySalesStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(total_amount), 0)
                 FROM orders
                 WHERE created_at::date = CURRENT_DATE"
            );
            $todaySalesStmt->execute();
            $todaySales = (float) $todaySalesStmt->fetchColumn();

            // Today's order count.
            $todayOrdersStmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM orders
                 WHERE created_at::date = CURRENT_DATE"
            );
            $todayOrdersStmt->execute();
            $todayOrders = (int) $todayOrdersStmt->fetchColumn();

            // Total order count (all time).
            $totalOrdersStmt = $pdo->prepare('SELECT COUNT(*) FROM orders');
            $totalOrdersStmt->execute();
            $totalOrders = (int) $totalOrdersStmt->fetchColumn();

            // Total product count.
            $totalProductsStmt = $pdo->prepare('SELECT COUNT(*) FROM products');
            $totalProductsStmt->execute();
            $totalProducts = (int) $totalProductsStmt->fetchColumn();

            // Last 7 days daily sales breakdown (for a sparkline/bar chart).
            $monthlySalesStmt = $pdo->prepare(
                "SELECT TO_CHAR(created_at::date, 'YYYY-MM-DD') AS date,
                        COALESCE(SUM(total_amount), 0) AS total
                 FROM orders
                 WHERE created_at::date >= CURRENT_DATE - INTERVAL '6 days'
                 GROUP BY created_at::date
                 ORDER BY created_at::date ASC"
            );
            $monthlySalesStmt->execute();
            $rawDailySales = $monthlySalesStmt->fetchAll();

            // Build a complete 7-day array (fill missing days with 0).
            $dailyMap = [];
            foreach ($rawDailySales as $row) {
                $dailyMap[$row['date']] = (float) $row['total'];
            }

            $monthlySales = [];
            for ($i = 6; $i >= 0; $i--) {
                $date           = date('Y-m-d', strtotime("-{$i} days"));
                $monthlySales[] = [
                    'date'  => $date,
                    'total' => $dailyMap[$date] ?? 0.0,
                ];
            }

            return Response::success('Dashboard data retrieved.', [
                'today_sales'    => $todaySales,
                'today_orders'   => $todayOrders,
                'total_orders'   => $totalOrders,
                'total_products' => $totalProducts,
                'monthly_sales'  => $monthlySales,
            ]);
        } catch (Throwable $e) {
            error_log('DashboardController::index error: ' . $e->getMessage());
            return Response::error('Failed to retrieve dashboard data.', 500);
        }
    }
}
