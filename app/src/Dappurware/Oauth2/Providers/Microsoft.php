<?php

namespace Dappur\Dappurware\Oauth2\Providers;

use Interop\Container\ContainerInterface;

class Microsoft extends \Dappur\Dappurware\Oauth2
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
        $userInfo['first_name'] = $returnedInfo->first_name;
        $userInfo['last_name'] = $returnedInfo->last_name;

        if (isset($returnedInfo->emails->account) && $returnedInfo->emails->account != "") {
            $userInfo['email'] = $returnedInfo->emails->account;
        }
        return $userInfo;
    }
}
