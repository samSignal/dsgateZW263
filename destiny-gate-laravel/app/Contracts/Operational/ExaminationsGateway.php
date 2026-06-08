<?php

namespace App\Contracts\Operational;

interface ExaminationsGateway
{
    public function activate(array $ctx, bool $dryRun): array;
    public function rollback(array $ctx, array $rollbackMeta): array;
    public function reconcile(array $ctx): array;
    public function health(): array;
}

