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

        $user = $this->findOrCreateUser($user, $provider);

        Auth::login($user);


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

    /**
     * @param $providerUser
     * @param $provider
     * @return User
     */
    protected function findOrCreateUser($providerUser, $provider){
        $user = User::where('email', $providerUser->getEmail())->first();
        if ($user) {
            if(!$user->verified_at){
                $user->verified_at = now();
                $user->save();
            }
            return $user;
        }

        $user = User::firstOrCreate(
            ['email' => $providerUser->getEmail()],
            [
                'email_verified_at' => now(),
                'name' => $providerUser->getName(),
                "avatar" => $providerUser->getAvatar()
            ]
        );

        $user->socialProviders()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
            ], []
        );
        return $user;
    }
}
