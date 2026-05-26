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
        \App\Console\Commands\AdmissionsArchiveInactiveDrafts::class,
        \App\Console\Commands\AdmissionsArchiveIntakeClosedDrafts::class,
        \App\Console\Commands\AdmissionsSendExpiryWarnings::class,
        \App\Console\Commands\AdmissionsCleanupVerificationChallenges::class,
        \App\Console\Commands\AdmissionsCleanupSecurityCounters::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('admissions:archive-inactive-drafts')->dailyAt('01:10');
        $schedule->command('admissions:archive-intake-closed-drafts')->hourly();
        $schedule->command('admissions:send-expiry-warnings')->dailyAt('07:30');
        $schedule->command('admissions:cleanup-verification-challenges')->everyTenMinutes();
        $schedule->command('admissions:cleanup-security-counters')->dailyAt('02:10');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
