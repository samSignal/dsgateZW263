<?php

namespace App\Support\AdmissionsOffice;

use App\Support\Admissions\AdmissionResponder;
use Illuminate\Http\Request;

class AdmissionOfficeAccess
{
    public static function requirePermission(Request $request, string $permission)
    {
        $user = $request->user();
        if (!$user) {
            return AdmissionResponder::fail('UNAUTHORIZED', 'Authentication required.', 401);
        }

        if (!$user->can($permission)) {
            return AdmissionResponder::fail('FORBIDDEN', 'You do not have permission to perform this action.', 403);
        }

        return null;
    }

    public static function actorMeta(Request $request): array
    {
        $user = $request->user();
        $roleNames = [];
        if ($user && method_exists($user, 'getRoleNames')) {
            $roleNames = $user->getRoleNames()->values()->all();
        }
        return [
            'actor_user_id' => $user?->id,
            'actor_role' => $user?->role,
            'actor_roles' => $roleNames,
        ];
    }
}
