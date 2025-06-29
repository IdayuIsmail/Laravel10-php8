<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client; 
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();
        return redirect()->intended('dashboard');
    }

    public function redirectToKeycloak()
    {
        $config = config('keycloak-web');

        $url = $config['base_url'] . '/realms/' . $config['realm'] . '/protocol/openid-connect/auth?' . http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid profile email',
        ]);
        return redirect()->away($url);
    }

    private function getAccessTokenFromKeycloak($code)
    {
        $config = config('keycloak-web');
        $client = new Client();
        try {
            $response = $client->post($config['base_url'] . '/realms/' . $config['realm'] . '/protocol/openid-connect/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'], 
                    'code' => $code,
                    'redirect_uri' => $config['redirect_uri'],
                ],
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getUserInfoFromKeycloak($token)
    {
        $config = config('keycloak-web');
        $client = new Client();
        $response = $client->get($config['base_url'] . '/realms/' . $config['realm'] . '/protocol/openid-connect/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token->access_token,
            ],
        ]);
        return json_decode($response->getBody()->getContents());
    }

    public function handleKeycloakCallback(Request $request)
    {
        $code = $request->input('code');
        if (!$code) {
            return redirect('/login')->withErrors(['error' => 'Authorization code not received.']);
        }
        $token = $this->getAccessTokenFromKeycloak($code);
        if (!$token) {
            return redirect('/login')->withErrors(['error' => 'Failed to retrieve access token from Keycloak.']);
        }
        $userInfo = $this->getUserInfoFromKeycloak($token);
        $nric = $userInfo->nric ?? null; 
        $name = $userInfo->nama ?? 'New User'; 
        session([ 'nric' => $nric, 'name' => $name,'keycloak_id_token' => $token->id_token]);

        // agency business logic - lookup nric in the database
        $user = User::whereNotNull('ic_number')->get()->filter(function ($user) use ($nric) {
            return Hash::check($nric, $user->ic_number);
        })->first();
        // agency business logic
        if ($user && Hash::check($nric, $user->ic_number)) {
            Auth::login($user);
            session()->regenerate();
            return redirect()->intended('dashboard');
        } else {
            if (!$user) {
                return redirect()->route('icnumber.notfound');
            } else {
                return redirect()->route('icnumber.link');
            }
        }
    }

public function logout()
{
    $idToken = session('keycloak_id_token');
    $logoutUrl = env('KEYCLOAK_BASE_URL') . "/realms/" .env('KEYCLOAK_REALM') . "/protocol/openid-connect/logout?" . http_build_query([
        'id_token_hint'             => $idToken,
        'post_logout_redirect_uri' => url('/'),
        'client_id'                 => env('KEYCLOAK_CLIENT_ID')
    ]);
    return redirect()->away($logoutUrl);
}

}
