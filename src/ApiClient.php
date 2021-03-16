<?php

use GuzzleHttp\Client;


class ApiClient 
{
    const base_url = 'https://staging-api.bixie.cloud/v1/';
    private Client $client;
    private String $token;

    function __construct() {
        $client = new GuzzleHttp\Client(['base_uri' => self::base_url]);
        $token = $_SESSION['bixie_api_token'];
    }

    function isLoggedIn()
    {        
        return empty( $token );
    }

    function login( String $username, String $password )
    {
        $body = array(
            'username'    =>  $username,
            'password'    =>  $password );

        $response = $client->post( '/token', [
            'body' => json_encode( $body )
        ] );

        if( $response->getStatusCode() == 200 )
        {
            $token = (string) $response->getBody();
            $_SESSION['bixie_api_token'] = $token;
            return true;
        }

        return false;
    }


}



?>