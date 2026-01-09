<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenant
{
    public function handle(Request $request, Closure $next): Response
{
    $headerName = env('TENANT_HEADER', 'X-Company-DB');

    $raw = $request->header($headerName)
        ?? $request->query('company')
        ?? $request->input('company');

    if (!$raw) {
        return response()->json(['message' => "Missing {$headerName} header"], 400);
    }

    // Load tenants.json from env
    $tenantFile = base_path(env('TENANT_JSON', 'storage/app/tenants.json'));
    if (!is_file($tenantFile)) {
        return response()->json(['message' => "Tenants file not found"], 500);
    }

    $tenants = json_decode(file_get_contents($tenantFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return response()->json(['message' => "Invalid tenants.json format"], 500);
    }

    $tenant = collect($tenants)->first(function ($t) use ($raw) {
        return isset($t['code'], $t['database']) &&
               (strcasecmp($t['code'], $raw) === 0 || strcasecmp($t['database'], $raw) === 0);
    });

    if (!$tenant || empty($tenant['database'])) {
        return response()->json(['message' => "Invalid tenant: {$raw}"], 404);
    }

    // Build tenant connection (use .env defaults if fields missing in JSON)
    \Config::set("database.connections.tenant", [
        'driver'   => 'sqlsrv',
        'host'     => $tenant['host']     ?? env('DB_HOST'),
        'port'     => $tenant['port']     ?? env('DB_PORT', '1433'),
        'database' => $tenant['database'],
        'username' => $tenant['username'] ?? env('DB_USERNAME'),
        'password' => $tenant['password'] ?? env('DB_PASSWORD'),
        'charset'  => 'utf8',
        'prefix'   => '',
        // important for ODBC 17/18 behavior
        'encrypt'  => env('DB_ENCRYPT', 'no'),
        'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', true),
    ]);

    \DB::purge('tenant');
    try {
        \DB::connection('tenant')->getPdo(); // test now; fail fast if wrong host
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Tenant database unavailable',
            'tenant'  => [
                'code' => $tenant['code'] ?? $raw,
                'database' => $tenant['database'],
                'host' => $tenant['host'] ?? env('DB_HOST'),
                'port' => $tenant['port'] ?? env('DB_PORT', '1433'),
            ],
            'error' => $e->getMessage(),
        ], 503);
    }

    \DB::setDefaultConnection('tenant');

    $request->attributes->set('tenant.database', $tenant['database']);
    $request->attributes->set('tenant.code', $tenant['code'] ?? null);
    $request->attributes->set('tenant.company', $tenant['company'] ?? null);

    return $next($request);
    
    }

}
