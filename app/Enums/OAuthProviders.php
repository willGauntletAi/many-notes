<?php

namespace App\Enums;

Enum OAuthProviders: string
{
    case Authentik = 'authentik';
    case Bitbucket = 'bitbucket';
    case Facebook = 'facebook';
    case GitHub = 'github';
    case GitLab = 'gitlab';
    case Google = 'google';
    case Keycloak = 'keycloak';
    case LinkedIn = 'linkedin';
    case Slack = 'slack';
    case Twitter = 'twitter';
}
