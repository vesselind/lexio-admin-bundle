<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Enum;

enum SecurityEvents: string
{
    use TranslatableEnum;

    case SWITCH_USER = 'switch_user';
    case INTERACTIVE_LOGIN = 'interactive_login';

    case LOGIN = 'login';
    case LOGIN_FAILURE = 'login_failure';
    case LOGOUT = 'logout';
    case PASSWORD_CHANGE = 'password_change';
    case PASSWORD_CHANGE_FAILED = 'password_change_failed';
    case PASSWORD_RESET_REQUEST = 'password_reset_request';

    case USER_REGISTERED = 'user_registered';

}
