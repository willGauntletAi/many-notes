<?php

namespace App\Enums;

Enum OAuthProviders: string
{
    case Bitbucket = 'bitbucket';
    case Facebook = 'facebook';
    case GitHub = 'github';
    case GitLab = 'gitlab';
    case Google = 'google';
    case LinkedIn = 'linkedin';
    case Slack = 'slack';
    case Twitter = 'twitter';
}
