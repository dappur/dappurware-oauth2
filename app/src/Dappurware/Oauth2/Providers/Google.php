<?php

namespace Dappur\Dappurware\Oauth2\Providers;

use Interop\Container\ContainerInterface;

class Google extends \Dappur\Dappurware\Oauth2
{
    protected function getUser($token, $resourceUrl)
    {
        $returnedInfo = parent::apiRequest(
            $resourceUrl,
            false,
            array('Authorization: Bearer '. $token->access_token),
            true
        );
        $userInfo['uid'] = $returnedInfo->id;
        $userInfo['first_name'] = $returnedInfo->given_name;
        $userInfo['last_name'] = $returnedInfo->family_name;

        if (isset($returnedInfo->email) && $returnedInfo->email != "") {
            $userInfo['email'] = $returnedInfo->email;
        }
        return $userInfo;
    }
}
