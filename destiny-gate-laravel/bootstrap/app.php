<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\BackfillStudentStreams::class,
        \App\Console\Commands\SyncUserRoles::class,
        \App\Console\Commands\ExpireStaleOffers::class,
        \App\Console\Commands\RenumberStudents::class,
        \App\Console\Commands\BackfillStudentLogins::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('dgi:expire-stale-offers')->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'       => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();
