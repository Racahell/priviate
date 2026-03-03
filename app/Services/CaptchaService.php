<?php

namespace App\Services;

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

    public function verify($input)
    {
        $expected = Session::get('captcha_result');
        return (int)$input === (int)$expected;
    }
}
