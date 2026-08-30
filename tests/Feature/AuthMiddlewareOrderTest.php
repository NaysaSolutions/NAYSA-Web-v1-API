<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyTenant;
use App\Http\Middleware\EnsureRecentActivity;
use Tests\TestCase;

class AuthMiddlewareOrderTest extends TestCase
{
    public function test_me_selects_the_tenant_before_resolving_the_authenticated_user(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === 'api/me');

        $this->assertNotNull($route);

        $middleware = $route->gatherMiddleware();

        $this->assertSame(ApplyTenant::class, app('router')->getMiddleware()['tenant']);

        $tenantPosition = array_search('tenant', $middleware, true);
        $activityPosition = array_search(EnsureRecentActivity::class, $middleware, true);

        $this->assertNotFalse($tenantPosition);
        $this->assertNotFalse($activityPosition, implode(', ', $middleware));
        $this->assertLessThan($activityPosition, $tenantPosition);

        $this->assertSame(1, collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($candidate) => $candidate->uri() === 'api/me')
            ->count());
    }
}
