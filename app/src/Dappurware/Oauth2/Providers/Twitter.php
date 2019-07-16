<?php

namespace Dappur\Dappurware\Oauth2\Providers;

use Interop\Container\ContainerInterface;

class Twitter extends \Dappur\Dappurware\Oauth2
{
    protected function getUser($token, $resourceUrl)
    {
        $connection = new \Abraham\TwitterOAuth\TwitterOAuth(
            $this->container->settings['oauth2']['twitter']['client_id'],
            $this->container->settings['oauth2']['twitter']['client_secret'],
            $token->access_token,
            $token->token_secret
        );
        $returnedInfo = $connection->get($resourceUrl, array("include_email" => "true"));

        $userInfo['uid'] = $token->uid;
        $fullName = explode(' ', $returnedInfo->name, 2);
        $userInfo['first_name'] = $fullName[0];
        $userInfo['last_name'] = $fullName[1];

        if (isset($returnedInfo->email) && $returnedInfo->email != "") {
            $userInfo['email'] = $returnedInfo->email;
        }

        return $userInfo;
    }

    public function accessToken()
    {
        $twConnection = new \Abraham\TwitterOAuth\TwitterOAuth(
            $this->container->settings['oauth2']['twitter']['client_id'],
            $this->container->settings['oauth2']['twitter']['client_secret']
        );

        $twArray = array();
        try {
            $accessToken = $twConnection->oauth(
                "oauth/access_token",
                [
                    "oauth_token" => $this->request->getParam('oauth_token'),
                    "oauth_verifier" => $this->request->getParam('oauth_verifier')
                ]
            );
            $twArray['access_token'] = $accessToken['oauth_token'];
            $twArray['token_secret'] = $accessToken['oauth_token_secret'];
            $twArray['uid'] = $accessToken['user_id'];
            $twArray['screen_name'] = $accessToken['screen_name'];
            $twArray['expires_in'] = $accessToken['x_auth_expires'];
        } catch (\Abraham\TwitterOAuth\TwitterOAuthException $e) {
            $twArray['error'] = (object) array(
                "message" => (string) "An error occured.  Please try again.");
        }

        $token = (object) $twArray;

        return $token;
    }
}
