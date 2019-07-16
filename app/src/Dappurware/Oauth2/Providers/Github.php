<?php

namespace Dappur\Dappurware\Oauth2\Providers;

use Interop\Container\ContainerInterface;

class Github extends \Dappur\Dappurware\Oauth2
{
    protected function getUser($token, $resourceUrl)
    {
        $returnedInfo = parent::apiRequest(
            $resourceUrl,
            false,
            array('Authorization: Bearer '. $token->access_token,
                'User-Agent:' . $this->container->request->getHeader('HTTP_USER_AGENT')),
            true
        );
    
        $userInfo['uid'] = $returnedInfo->id;
        $fullName = explode(' ', $returnedInfo->name, 2);
        $userInfo['first_name'] = $fullName[0];
        $userInfo['last_name'] = $fullName[1];

        if (isset($returnedInfo->email) && $returnedInfo->email != "") {
            $userInfo['email'] = $returnedInfo->email;
        }
        return $userInfo;
    }

    protected function accessToken($provider)
    {
        // Verify the state matches our stored state
        $oauth2State = $this->container->session->get('oauth2-state');
        $returnedState = $this->container->request->getParam('state');

        if ((string) $oauth2State !== (string) $returnedState) {
            $output['error'] = (object) array(
                "message" => (string) "Oauth2 Error: Session state did not match. Please try again.");
            return (object) $output;
        }

        // Get Github access token
        $token = $this->apiRequest(
            $provider->token_url,
            array(
                'client_id' => $this->container->settings['oauth2'][$provider->slug]['client_id'],
                'client_secret' => $this->container->settings['oauth2'][$provider->slug]['client_secret'],
                'code' => $this->container->request->getParam('code'),
            ),
            array(
                'User-Agent: ' . $this->container->settings['oauth2'][$provider->slug]['app_name'],
                'Content-type: application/x-www-form-urlencoded'
            ),
            false
        );

        parse_str($token, $output);
        $output['expires_in'] = 0;
        $token = (object) $output;

        return $token;
    }
}
