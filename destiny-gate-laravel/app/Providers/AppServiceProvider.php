<?php

namespace App\Providers;

use App\Contracts\Operational\AttendanceGateway;
use App\Contracts\Operational\ExaminationsGateway;
use App\Contracts\Operational\HostelGateway;
use App\Contracts\Operational\LibraryGateway;
use App\Contracts\Operational\LmsProvisioningGateway;
use App\Contracts\Operational\TimetableGateway;
use App\Contracts\Operational\TransportGateway;
use App\Support\Operational\Gateways\AttendanceGatewayDb;
use App\Support\Operational\Gateways\ExaminationsGatewayDb;
use App\Support\Operational\Gateways\HostelGatewayDb;
use App\Support\Operational\Gateways\LibraryGatewayDb;
use App\Support\Operational\Gateways\LmsProvisioningGatewayDb;
use App\Support\Operational\Gateways\TimetableGatewayDb;
use App\Support\Operational\Gateways\TransportGatewayDb;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TimetableGateway::class, TimetableGatewayDb::class);
        $this->app->bind(AttendanceGateway::class, AttendanceGatewayDb::class);
        $this->app->bind(LmsProvisioningGateway::class, LmsProvisioningGatewayDb::class);
        $this->app->bind(ExaminationsGateway::class, ExaminationsGatewayDb::class);
        $this->app->bind(LibraryGateway::class, LibraryGatewayDb::class);
        $this->app->bind(TransportGateway::class, TransportGatewayDb::class);
        $this->app->bind(HostelGateway::class, HostelGatewayDb::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
