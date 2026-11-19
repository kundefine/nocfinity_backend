<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function stats()
    {
        $today = Carbon::today();

        // 1. Today's orders count
        $todayOrders = Order::whereDate('created_at', $today)->count();

        // 2. Today's revenue (from paid/completed orders)
        $todayRevenue = (float) Order::whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // 3. Total customers count (users without staff roles)
        $totalCustomers = User::whereDoesntHave('roles')->count();

        // 4. Low-stock count (stock <= 5)
        $lowStockCount = Product::active()->where('stock', '<=', 5)->count();

        // 5. 30-day sales chart data
        $startDate = Carbon::today()->subDays(29);
        $rawSales = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN payment_status = "paid" THEN total_amount ELSE 0 END) as revenue')
            )
            ->whereDate('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        $chartData = [];
        for ($i = 0; $i < 30; $i++) {
            $dateStr = $startDate->copy()->addDays($i)->format('Y-m-d');
            $row = $rawSales->get($dateStr);
            $chartData[] = [
                'date' => $dateStr,
                'orders' => $row ? (int) $row->total_orders : 0,
                'revenue' => $row ? (float) $row->revenue : 0,
            ];
        }

        // 6. Recent orders
        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        return $this->success([
            'metrics' => [
                'today_orders' => $todayOrders,
                'today_revenue' => $todayRevenue,
                'total_customers' => $totalCustomers,
                'low_stock_count' => $lowStockCount,
            ],
            'chart_data' => $chartData,
            'recent_orders' => $recentOrders,
        ]);
    }

    public function customers(Request $request)
    {
        $query = User::whereDoesntHave('roles')
            ->withCount('orders')
            ->withSum([
                'orders' => function ($q) {
                    $q->where('payment_status', 'paid');
                }
            ], 'total_amount');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $customers = $query->latest()->paginate($request->get('per_page', 20));

        return $this->paginated($customers);
    }
}
