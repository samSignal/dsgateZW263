<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait AssertsAdmissionDomainEvents
{
    protected function assertDomainEventStored(string $eventName, ?int $applicationId = null): void
    {
        $q = DB::table('admission_domain_events')->where('event_name', $eventName);
        if ($applicationId !== null) $q->where('application_id', $applicationId);
        $this->assertTrue($q->exists(), "Expected domain event not found: {$eventName}");
    }
}

