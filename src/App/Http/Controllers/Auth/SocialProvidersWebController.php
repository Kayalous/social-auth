<?php

namespace Kayalous\SocialAuth\App\Http\Controllers\Auth;

use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
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
    public function redirectToProvider($provider, Request $request)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }

        // if the request has no-create, then we need to add a query string to the redirect url
        if($request['no-create'])

        {
            $envName = strtoupper($provider) . '_REDIRECT_URI';

            $baseUrl = URL::to('/') . '/auth/login/' . $provider . '/callback';

            $redirect_to = env($envName, $baseUrl) . '?no-create=' . $request['no-create'] . '&no-create-url=' . $request['no-create-url'];

            return Socialite::redirectUrl($redirect_to)->driver($provider)->redirect();

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
        

        if(!User::where('email', $user->getEmail())->exists()) {
            if($request['no-create']) {

                return redirect()->to($request['no-create-url'] ?? '/register')->with('error', 'You must create an account to login with ' . $provider . '.');
    
            }        
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
            if(!$user->email_verified_at){
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
            ], []
        );
        return $user;
    }
}
