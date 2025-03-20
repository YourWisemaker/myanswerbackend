<?php
//Main Functions:
namespace App\Http\Controllers;

use App\Facades\Context;
use Illuminate\Http\Request;

class Task51Controller extends Controller
{
    public function index()
    {
        Context::put('username', 'john_doe');
        Context::put('role', 'admin');
        
        $this->logUserActivity();
        $this->checkPermissions();
        
        return response()->json([
            'message' => 'Context example',
            'data' => [
                'username' => Context::get('username'),
                'role' => Context::get('role'),
                'hasPermission' => Context::get('hasPermission', false)
            ]
        ]);
    }
    
    private function logUserActivity()
    {
        $username = Context::get('username');
        
        return "Logged activity for user: {$username}";
    }
    
    private function checkPermissions()
    {
        $role = Context::get('role');
        Context::put('hasPermission', $role === 'admin');
    }
}


Here is example how to call Facade context :
//this one inside Context.php inside Services


namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Context extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'context';
    }
}

//and then this one inside ContextServiceProvider.php inside Providers:


namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ContextServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('context', function ($app) {
            return new \stdClass();
        });
    }

    public function boot()
    {
        //
    }
}