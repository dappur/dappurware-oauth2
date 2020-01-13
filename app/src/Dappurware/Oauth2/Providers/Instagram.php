<?php

namespace Dappur\Dappurware\Oauth2\Providers;

use Psr\Container\ContainerInterface;

class Instagram extends \Dappur\Dappurware\Oauth2
{
    protected function getUser($token, $resourceUrl)
    {
        $userInfo['uid'] = $token->user->id;
        $fullName = explode(' ', $token->user->full_name, 2);
        $userInfo['first_name'] = $fullName[0];
        $userInfo['last_name'] = $fullName[1];

        return $userInfo;
    }
}
