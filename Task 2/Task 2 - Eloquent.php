<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        // Eager load relationships and preload data in a single query and it could be faster too
        $orders = Order::with(['customer', 'items.product'])
            ->select(['orders.*'])
            ->addSelect([
                'last_cart_date' => CartItem::select('created_at')
                    ->whereColumn('order_id', 'orders.id')
                    ->orderByDesc('created_at')
                    ->limit(1)
            ])
            ->get();

        // Preload completed_at dates for all orders to avoid N+1 queries during sorting
        $completedDates = Order::where('status', 'completed')
            ->whereIn('id', $orders->pluck('id'))
            ->select('id', 'completed_at')
            ->get()
            ->keyBy('id');

        $orderData = [];

        foreach ($orders as $order) {
            // Calculate total amount and items count
            $totalAmount = 0;
            $itemsCount = 0;
            
            foreach ($order->items as $item) {
                $totalAmount += $item->price * $item->quantity;
                $itemsCount++;
            }

            // Convert string dates to Carbon instances
            $lastCartDate = $order->last_cart_date ? new \Carbon\Carbon($order->last_cart_date) : null;
            $completedAt = isset($completedDates[$order->id]) ? 
                new \Carbon\Carbon($completedDates[$order->id]->completed_at) : null;

            $orderData[] = [
                'order_id' => $order->id,
                'customer_name' => $order->customer->name,
                'total_amount' => $totalAmount,
                'items_count' => $itemsCount,
                'last_added_to_cart' => $lastCartDate,
                'completed_order_exists' => $order->status === 'completed',
                'created_at' => $order->created_at,
                'completed_at' => $completedAt
            ];
        }

        // Sort using the preloaded completed_at dates
        usort($orderData, function($a, $b) {
            $aTime = $a['completed_at'] ? $a['completed_at']->timestamp : 0;
            $bTime = $b['completed_at'] ? $b['completed_at']->timestamp : 0;
            
            return $bTime - $aTime; // Descending order
        });

        return view('orders.index', ['orders' => $orderData]);
    }
}