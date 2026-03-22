<?php

namespace App\Services\Chat;

use Illuminate\Session\Store;
use Illuminate\Support\Str;

class SupportChatSessionService
{
    public const SESSION_KEY = 'support_chat_session_token';

    public function token(Store $session): string
    {
        $token = $session->get(self::SESSION_KEY);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $token = Str::random(40);
        $session->put(self::SESSION_KEY, $token);

        return $token;
    }
}
