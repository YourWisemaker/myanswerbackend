<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Task52Controller extends Controller
{
    public function index()
    {
        $value = Cache::remember('users.count', 3600, function () {
            return $this->getExpensiveUserCount();
        });
        
        if (!Cache::has('last_system_update')) {
            Cache::put('last_system_update', now(), 86400); // 24 hours
        }
        
        Cache::put('user.1.profile', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ], 3600);
        
        Cache::increment('page.views', 1);
        $pageViews = Cache::get('page.views', 0);
        
        return response()->json([
            'message' => 'Cache example',
            'data' => [
                'users_count' => $value,
                'last_update' => Cache::get('last_system_update'),
                'user_profile' => Cache::get('user.1.profile'),
                'page_views' => $pageViews
            ]
        ]);
    }
    
    private function getExpensiveUserCount()
    {
        // Simulate an expensive database query
        sleep(1);
        return 42; // Simulated user count
    }
}