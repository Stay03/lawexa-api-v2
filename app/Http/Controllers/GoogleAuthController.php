<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OAuthState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        
        return response()->json([
            'url' => $url
        ]);
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('google_id', $googleUser->id)
                       ->orWhere('email', $googleUser->email)
                       ->first();

            if ($user) {
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar
                    ]);
                }
            } else {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'user'
                ]);
            }

            $token = $user->createToken('google_auth_token')->plainTextToken;

            // Create temporary code for secure token exchange
            $oauthState = OAuthState::createWithCode($token, $user->toArray());
            
            // Clean expired codes
            OAuthState::cleanExpired();

            // Redirect to frontend with temporary code
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect()->away($frontendUrl . '/auth/callback?code=' . $oauthState->code);

        } catch (\Exception $e) {
            // Create temporary code for secure error exchange
            $oauthState = OAuthState::createWithError('oauth_error', $e->getMessage());
            
            // Clean expired codes
            OAuthState::cleanExpired();
            
            // Redirect to frontend with temporary code (consistent with success)
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect()->away($frontendUrl . '/auth/callback?code=' . $oauthState->code);
        }
    }

    public function exchangeCodeForToken(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:32'
        ]);

        $oauthState = OAuthState::where('code', $request->code)->first();

        if (!$oauthState) {
            return ApiResponse::error('Invalid or expired code', null, 400);
        }

        if ($oauthState->isExpired()) {
            $oauthState->delete();
            return ApiResponse::error('Code has expired', null, 400);
        }

        if ($oauthState->is_error) {
            $errorResource = new ErrorResource([
                'error_code' => $oauthState->error_code,
                'error_message' => $oauthState->error_message,
                'details' => 'Google OAuth authentication failed'
            ]);
            
            $oauthState->delete();
            
            return ApiResponse::error(
                'Google authentication failed',
                $errorResource->toArray($request),
                400
            );
        }

        $user = User::find($oauthState->user_data['id']);
        $userResource = $user ? new UserResource($user) : $oauthState->user_data;

        $response = [
            'token' => $oauthState->token,
            'user' => $userResource
        ];

        $oauthState->delete();

        return ApiResponse::success($response, 'Google authentication successful');
    }
}
