<?php

namespace Dappur\Dappurware\Oauth2\Providers;

use Psr\Container\ContainerInterface;

class Linkedin extends \Dappur\Dappurware\Oauth2
{
    protected function getUser($token, $resourceUrl)
    {
        $returnedInfo = parent::apiRequest(
            $resourceUrl,
            false,
            array('Authorization: Bearer '. $token->access_token),
            true
        );

        $returnedEmail = parent::apiRequest(
            'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))',
            false,
            array('Authorization: Bearer '. $token->access_token),
            true
        );

        $userInfo['uid'] = $returnedInfo->id;
        $userInfo['first_name'] = $returnedInfo->localizedFirstName;
        $userInfo['last_name'] =$returnedInfo->localizedLastName;

        if (isset($returnedEmail->elements[0]->{'handle~'}->emailAddress) && $returnedEmail->elements[0]->{'handle~'}->emailAddress != "") {
            $userInfo['email'] = $returnedEmail->elements[0]->{'handle~'}->emailAddress;
        }

        return $userInfo;
    }
}
