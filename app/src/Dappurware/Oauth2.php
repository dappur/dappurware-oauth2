<?php

namespace Dappur\Dappurware;

use Psr\Container\ContainerInterface;

class Oauth2 extends Dappurware
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    public function apiRequest($url, $post = null, $headers = array(), $jsonDecode = null)
    {
        $channel = curl_init($url);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        if ($post) {
            curl_setopt($channel, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        curl_setopt($channel, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($channel, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($channel);

        if ($jsonDecode) {
            $test = json_decode($response);
            return $test;
        }

        return $response;
    }

    public function buildBaseString($baseURI, $params)
    {
        $output = array();
        ksort($params);
        foreach ($params as $key => $value) {
            $output[] = "$key=" . rawurlencode($value);
        }

        return "POST&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $output));
    }

    public function getCompositeKey($consumerSecret, $requestToken)
    {
        return rawurlencode($consumerSecret) . '&' . rawurlencode($requestToken);
    }

    public function buildAuthorizationHeader($oauth)
    {
        $output = 'Authorization: OAuth ';
        $values = array();
        foreach ($oauth as $key => $value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }

        $output .= implode(', ', $values);
        return $output;
    }

    public function sendRequest($oauth, $baseURI)
    {
        $header = array( $this->buildAuthorizationHeader($oauth), 'Expect:');

        $options = array(CURLOPT_HTTPHEADER => $header,
                               CURLOPT_HEADER => false,
                               CURLOPT_URL => $baseURI,
                               CURLOPT_POST => true,
                               CURLOPT_RETURNTRANSFER => true,
                               CURLOPT_SSL_VERIFYPEER => false);

        $channel = curl_init();
        curl_setopt_array($channel, $options);
        $response = curl_exec($channel);
        curl_close($channel);

        return $response;
    }

    public function getUserInfo($token, $provider)
    {
        $slug = $provider->slug;

        if (!isset($this->container->settings['oauth2'][$provider->slug]['class'])) {
            throw new \Exception("Provider class is not defined in settings.");
        }

        $className = $this->container->settings['oauth2'][$provider->slug]['class'];

        if (!class_exists($className)) {
            throw new \Exception("Provider class is not accessible.");
        }
        
        $userInfo = new $className($this->container);

        $userInfo = $userInfo->getUser($token, $provider->resource_url);
        $userInfo['access_token'] = $token->access_token;
        if (isset($token->token_secret)) {
            $userInfo['token_secret'] = $token->token_secret;
        }
        if (isset($token->refresh_token)) {
            $userInfo['refresh_token'] = $token->refresh_token;
        }
        $userInfo['expires_in'] = $token->expires_in;

        return $userInfo;
    }


    private function getDefaultUser($token, $resourceUrl)
    {
        $returnedInfo = $this->apiRequest(
            $resourceUrl,
            false,
            array('Authorization: Bearer '. $token->access_token),
            true
        );
        if (isset($returnedInfo->id) && $returnedInfo->id != "") {
            $userInfo['uid'] = $returnedInfo->id;
        }
        return $userInfo;
    }

    public function getAccessToken($provider)
    {
        $slug = $provider->slug;

        if (!isset($this->container->settings['oauth2'][$provider->slug]['class'])) {
            throw new \Exception("Provider class is not defined in settings.");
        }

        $className = $this->container->settings['oauth2'][$provider->slug]['class'];
        if (!class_exists($className)) {
            throw new \Exception("Provider class is not accessible.");
        }

        if (method_exists($className, 'accessToken')) {
            $token = new $className($this->container);
            $token = $token->accessToken($provider);
            return $token;
        }
        
        // Verify the state matches our stored state
        $oauth2State = $this->container->session->get('oauth2-state');
        $returnedState = $this->container->request->getParam('state');

        
        if ((string) $oauth2State !== (string) $returnedState) {
            (object) $token['error'] = (object) array(
                "message" => (string) "Oauth2 Error: Session state did not match. Please try again."
            );
            return $token;
        }

        $currentUrl = $this->container->request->getUri()->getBaseUrl() .
            $this->container->request->getUri()->getPath();

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

        return $token;
    }
}
