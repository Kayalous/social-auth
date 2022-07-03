<?php

namespace Kayalous\SocialAuth\App\Http\Controllers\Auth;

use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kayalous\SocialAuth\App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use function now;
use function response;

class SocialProvidersWebController extends Controller
{

    private $providers = ['facebook', 'apple', 'google', 'github', 'linkedin'];

    /**
     * Redirect the user to the Provider authentication page.
     *
     * @param $provider
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToProvider($provider)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from Provider.
     *
     * @param $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback($provider, Request $request)
    {
        try {
            $user = Socialite::driver($provider)->user();
        } catch (ClientException $exception) {
            return redirect()->back()->withErrors($exception->getMessage());
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
            ], []
        );

        Auth::login($userCreated);


        return redirect()->intended();

    }

    /**
     * @param $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function validateProvider($provider)
    {
        if (!in_array($provider, $this->providers)) {
            return redirect()->back()->withErrors('Please login using one of the following: ' . implode(",", $this->providers));
        }
    }
}
