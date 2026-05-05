<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CookieConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9\-_]+$/'],
            'policy_version' => ['required', 'string', 'max:20'],
            'analytics' => ['required', 'boolean'],
            'necessary' => ['required', 'boolean'],
            'consent_ts' => ['nullable', 'integer', 'min:0'],
        ]);

        $clientId = (string) $data['client_id'];
        $policyVersion = (string) $data['policy_version'];
        $analytics = (bool) $data['analytics'];
        $necessary = (bool) $data['necessary'];
        $consentAt = !empty($data['consent_ts'])
            ? now()->setTimestamp((int) floor(((int) $data['consent_ts']) / 1000))
            : now();

        $ipHash = hash('sha256', (string) $request->ip() . '|' . config('app.key'));
        $userAgent = mb_substr((string) $request->userAgent(), 0, 500);

        $last = DB::table('cookie_consent_logs')
            ->where('client_id', $clientId)
            ->orderByDesc('id')
            ->first();

        if (
            $last
            && (bool) $last->analytics === $analytics
            && (bool) $last->necessary === $necessary
            && (string) $last->policy_version === $policyVersion
        ) {
            return response()->json(['ok' => true, 'deduplicated' => true]);
        }

        DB::table('cookie_consent_logs')->insert([
            'client_id' => $clientId,
            'policy_version' => $policyVersion,
            'analytics' => $analytics,
            'necessary' => $necessary,
            'consent_at' => $consentAt,
            'ip_hash' => $ipHash,
            'user_agent' => $userAgent,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
