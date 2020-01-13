<?php

namespace Dappur\Dappurware\Oauth2\Providers;

use Psr\Container\ContainerInterface;

class Facebook extends \Dappur\Dappurware\Oauth2
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

        if (isset($returnedInfo->email) && $returnedInfo->email != "") {
            $userInfo['email'] = $returnedInfo->email;
        }
        return $userInfo;
    }

    protected function accessToken($provider)
    {
        $currentUrl = $this->container->request->getUri()->getBaseUrl() .
            $this->container->request->getUri()->getPath();
        // Verify the state matches our stored state
        $oauth2State = $this->container->session->get('oauth2-state');
        $returnedState = $this->container->request->getParam('state');
        if ((string) $oauth2State !== (string) $returnedState) {
            $output['error'] = (object) array(
                "message" => (string) "Oauth2 Error: Session state did not match. Please try again.");
            return (object) $output;
        }
        // Get Access Token
        $token = $this->apiRequest(
            $provider->token_url,
            array(
                'client_id' => $this->container->settings['oauth2'][$provider->slug]['client_id'],
                'client_secret' => $this->container->settings['oauth2'][$provider->slug]['client_secret'],
                'redirect_uri' => $currentUrl,
                'code' => $this->container->request->getParam('code'),
                'grant_type' => 'authorization_code'
            ),
            array(),
            true
        );

        if (isset($token->access_token)) {
            // Get Long Lived Access Token
            $token = $this->apiRequest(
                $provider->token_url,
                array(
                    'client_id' => $this->container->settings['oauth2'][$provider->slug]['client_id'],
                    'client_secret' => $this->container->settings['oauth2'][$provider->slug]['client_secret'],
                    'fb_exchange_token' => $token->access_token,
                    'grant_type' => 'fb_exchange_token'
                ),
                array(),
                true
            );
            return $token;
        }

        $output['error'] = (object) array(
                "message" => (string) "An error occured.  Please try again.");
        return (object) $output;
    }
}
