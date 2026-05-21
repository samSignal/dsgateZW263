<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class SyncUserRoles extends Command
{
    protected $signature   = 'dgi:sync-roles';
    protected $description = 'Sync users.role column to Spatie roles';

    public function handle(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $users = DB::table('users')->get();
        $count = 0;

        foreach ($users as $u) {
            if (!$u->role) continue;
            $model = User::find($u->id);
            if ($model) {
                $model->syncRoles([$u->role]);
                $this->line("  ✓ {$u->name} → {$u->role}");
                $count++;
            }
        }

        $this->info("Done. {$count} users synced.");
        $this->info('Total role assignments: ' . DB::table('model_has_roles')->count());
    }
}
