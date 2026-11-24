<?php

use Illuminate\Support\Facades\Route;

// NOBTA is API-only. The web root returns a minimal identifier; the health check lives at /up.
// Stateless (B2-M1, ADR-021): without the `web` group no session is started, so an anonymous request
// never writes a row to the database `sessions` table and no session/XSRF cookies are issued.
Route::get('/', fn () => response()->json(['name' => config('app.name'), 'api' => '/api/v1']))
    ->withoutMiddleware('web');
