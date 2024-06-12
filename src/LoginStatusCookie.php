<?php declare(strict_types=1);
namespace pcak\BixieApi;

use DateTime;
use DateTimeZone;
use DateTimeInterface;

class LoginStatusCookie
{
    public static function set($logged_in, $onboarding)
    {
        $result_cookie = "logged-in";
        if ($logged_in == false) {
            $result_cookie = "logged-out";
        }
       
        $dt = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        $dt->modify('+1 hour');
        $timestamp = $dt->getTimestamp() + $dt->getOffset();
        \System::setCookie("bixie-login-status", $result_cookie, $timestamp);


        $onboarding_cookie = "no-onboarding";
        if ($onboarding == true) {
            $onboarding_cookie = "onboarding";
        }

        \System::setCookie("bixie-onboarding-status", $onboarding_cookie, $timestamp);
    }
}
