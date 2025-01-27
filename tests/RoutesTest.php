<?php

namespace romanzipp\QueueMonitor\Tests;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route as RouteFacade;
use romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController;

class RoutesTest extends TestCase
{
    public function testBasicRouteCreation()
    {
        RouteFacade::prefix('jobs')->group(function () {
            RouteFacade::queueMonitor();
        });

        $route = app(Router::class)->getRoutes()->getByAction('romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController@index');
        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals('jobs', $route->uri);
        $this->assertEquals('\romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController@index', $route->getAction('controller'));
    }

    public function testRouteCreationInNamespace()
    {
        RouteFacade::namespace('App\Http')->prefix('jobs')->group(function () {
            RouteFacade::queueMonitor();
        });

        $route = app(Router::class)->getRoutes()->getByAction('romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController@index'); // ->getByAction(ShowQueueMonitorController::class);
        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals('jobs', $route->uri);
        $this->assertEquals('\romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController@index', $route->getAction('controller'));
    }
}
