<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * MOPH Provider ID OAuth integration — interface stub.
 *
 * The actual flow is wired up when the MOPH integration docs arrive.
 * For now this controller exposes the endpoints the frontend needs and
 * demonstrates the JWT token issuance path so the rest of the system
 * (router guards, auth store, role gates) can be tested end-to-end.
 */
class ProviderIdController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $clientId = config('services.provider_id.client_id', env('PROVIDER_ID_CLIENT_ID'));
        $authUrl = config('services.provider_id.auth_url', env('PROVIDER_ID_AUTH_URL', ''));
        $redirect = url('/auth/provider-id/callback');
        $state = Str::random(40);

        // Persist state in session/cache for CSRF check on callback (simplified for stub).
        // In production: cache the state with a TTL and verify on callback.

        if (! $clientId || ! $authUrl) {
            return response()->json([
                'configured' => false,
                'message' => 'ระบบยังไม่ได้ตั้งค่า Provider ID — กรุณารอเอกสาร MOPH PID',
                'authorize_url' => null,
            ]);
        }

        $url = $authUrl.'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'response_type' => 'code',
            'state' => $state,
            'scope' => 'profile',
        ]);

        return response()->json([
            'configured' => true,
            'authorize_url' => $url,
            'state' => $state,
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['nullable', 'string'],
        ]);

        // TODO: exchange `code` for an access token at MOPH PID,
        //       fetch user profile, then upsert local user.
        // Stub response so frontend can verify the round-trip works:
        return response()->json([
            'message' => 'Provider ID callback received (stub). Real handshake will land in Phase 10.5.',
            'received_code' => substr($request->string('code'), 0, 8).'…',
        ]);
    }

    /**
     * Demo-only: link an existing local user to a provider id.
     * Used in development to test the JWT issuance path for "user" role.
     */
    public function demoExchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider_id' => ['required', 'string', 'max:255'],
        ]);

        $user = User::firstOrCreate(
            ['provider_id' => $data['provider_id']],
            [
                'hospital_id' => 1,
                'name' => 'Provider User '.Str::random(4),
                'full_name' => 'ผู้ใช้ Provider ID '.Str::random(4),
                'email' => 'pid_'.Str::lower(Str::random(8)).'@ck-mems.local',
                'password' => bcrypt(Str::random(32)),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if ($user->wasRecentlyCreated) {
            $user->syncRoles(['user']);
        }

        $token = Auth::guard('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $user->only(['id', 'full_name', 'email', 'provider_id']),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
