<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class CaptchaService
{
    public function generate()
    {
        $num1 = rand(1, 9);
        $num2 = rand(1, 9);
        $operator = rand(0, 1) ? '+' : '*';
        
        $result = $operator === '+' ? $num1 + $num2 : $num1 * $num2;
        
        Session::put('captcha_result', $result);
        
        return "Berapa hasil dari $num1 $operator $num2?";
    }

    public function verify(Request $request): bool
    {
        $offlineEnabled = (bool) env('CAPTCHA_OFFLINE_ENABLED', true);
        $connectionStatus = strtolower((string) $request->input('connection_status', 'online'));
        $isClientOnline = $connectionStatus === 'online';

        if ((bool) config('services.recaptcha.enabled')) {
            $token = $request->input('g-recaptcha-response');

            if ($isClientOnline) {
                if (empty($token)) {
                    return false;
                }

                try {
                    $response = Http::asForm()->timeout(4)->post(
                        config('services.recaptcha.verify_url'),
                        [
                            'secret' => config('services.recaptcha.secret_key'),
                            'response' => $token,
                            'remoteip' => $request->ip(),
                        ]
                    );

                    if ((bool) data_get($response->json(), 'success', false)) {
                        return true;
                    }
                } catch (\Throwable) {
                    return false;
                }

                return false;
            }
        }

        if (!$offlineEnabled) {
            return false;
        }

        $expected = Session::get('captcha_result');
        return (int) $request->input('captcha') === (int) $expected;
    }
}
