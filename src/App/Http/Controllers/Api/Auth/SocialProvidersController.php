<?php

namespace Kayalous\SocialAuth\App\Http\Controllers\Api\Auth;

use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kayalous\SocialAuth\App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use function now;
use function response;

class SocialProvidersController extends Controller
{

    private $providers = ['facebook', 'apple', 'google', 'github', 'linkedin'];

    /**
     * Redirect the user to the Provider authentication page.
     *
     * @param $provider
     * @return JsonResponse
     */
    public function redirectToProvider($provider)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }

        $redirectResponse = Socialite::driver($provider)->stateless()->redirect();
        $redirectUrl = $redirectResponse->getTargetUrl();
        return response()->json([
            'url' => $redirectUrl,
            'provider' => $provider
        ]);
    }

    /**
     * Obtain the user information from Provider using a token from a mobile app.
     *
     * @param $provider
     * @return JsonResponse
     */
    public function handleProviderCallbackToken($provider, Request $request)
    {
        $validated = $request->validate([
            'token' => 'string',
            'code'  => 'string'
        ]);
        $token = $request->token ?? $request->code;

        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }
        try {
            $user = Socialite::driver($provider)->stateless()->userFromToken($token);
        } catch (ClientException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $userCreated = User::firstOrCreate(
            ['email' => $user->getEmail()],
            [
                'email_verified_at' => now(),
                'name' => $user->getName(),
                "avatar" => $user->getAvatar()
            ]
        );

        $userCreated->socialProviders()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $user->getId(),
            ],
            []
        );

        return response()->json([
            'token' => $userCreated->createToken('token-name')->plainTextToken,
            'user' => $userCreated
        ]);
    }

    /**
     * Obtain the user information from Provider.
     *
     * @param $provider
     * @return JsonResponse
     */
    public function handleProviderCallback($provider, Request $request)
    {

        try {
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (ClientException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $user = $this->findOrCreateUser($user, $provider);


        return response()->json([
            'token' => $user->createToken('auth-token')->plainTextToken,
            'user' => $user
        ]);
    }

    /**
     * @param $provider
     * @return JsonResponse
     */
    protected function validateProvider($provider)
    {
        if (!in_array($provider, $this->providers)) {
            return response()->json(['error' => 'Please login using one of the following: ' . implode(",", $this->providers)], 422);
        }
    }

    /**
     * @param $providerUser
     * @param $provider
     * @return User
     */
    protected function findOrCreateUser($providerUser, $provider)
    {
        $user = User::where('email', $providerUser->getEmail())->first();
        if ($user) {
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }
            return $user;
        }



        $user = User::firstOrCreate(
            ['email' => $providerUser->getEmail()],
            [
                'email_verified_at' => now(),
                'name' => $providerUser->getName(),
                "avatar" => $providerUser->getAvatar(),
                'profile_photo_path' => $providerUser->getAvatar(),
                'photo' => $providerUser->getAvatar(),
            ]
        );

        $user->socialProviders()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
            ],
            []
        );
        return $user;
    }
}
