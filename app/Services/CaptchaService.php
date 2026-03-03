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
        if ((bool) config('services.recaptcha.enabled')) {
            $token = $request->input('g-recaptcha-response');

            if (!empty($token)) {
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
                    // Fallback to offline captcha
                }
            }
        }

        if (!(bool) env('CAPTCHA_OFFLINE_ENABLED', true)) {
            return false;
        }

        $expected = Session::get('captcha_result');
        return (int) $request->input('captcha') === (int) $expected;
    }
}
