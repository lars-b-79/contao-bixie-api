<?php declare(strict_types=1);
namespace pcak\BixieApi;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;


class ApiClient 
{
    const base_url = 'https://staging-api.bixie.cloud/v1/';
    const session_token_key = 'bixie_api_token';
    const session_zusagen_key = 'bixie_api_zusagen';
    const session_posteingang_key = 'bixie_api_posteingang';

    private \GuzzleHttp\Client $client;
    private String $token = '';
    private $zusagen;
    private $posteingang;
  


    public static function withStandardUrl() {
        $instance = new self();
        $instance->client = new \GuzzleHttp\Client(['base_uri' => self::base_url]);

        if( !isset($_SESSION) )
            $_SESSION = array();


        if( isset($_SESSION[self::session_token_key])) 
            $instance->token = $_SESSION['bixie_api_token'];

        if( isset($_SESSION[self::session_zusagen_key])) 
            $instance->zusagen = $_SESSION[self::session_zusagen_key];

        if( isset($_SESSION[self::session_posteingang_key])) 
            $instance->posteingang = $_SESSION[self::session_posteingang_key];


        return $instance;
    }

    public static function withTestHandler( $handlerStack ) {

        $instance = new self();
        $instance->client = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        if( !isset($_SESSION) )
            $_SESSION = array();

        if( isset($_SESSION[self::session_token_key])) 
            $instance->token = $_SESSION[self::session_token_key];

        if( isset($_SESSION[self::session_zusagen_key])) 
            $instance->zusagen = $_SESSION[self::session_zusagen_key];

        if( isset($_SESSION[self::session_posteingang_key])) 
            $instance->posteingang = $_SESSION[self::session_posteingang_key];


        return $instance;
    }

 
    public function isLoggedIn()
    {                 
        return !empty( $this->token );
    }

    public function login( String $username, String $password )
    {
        $body = array(
            'username'    =>  $username,
            'password'    =>  $password );

       
        $response = NULL;
        try {

            $response = $this->client->post( '/v1/token', [
                'body' => json_encode( $body ),
                'headers' => ['Content-Type' => 'application/json']                
            ] );
    
        } catch (ClientException | ServerException $e) {
            error_log( $e->getMessage() );
            $this->token = '';            
            return false;
        }
     
        if( $response->getStatusCode() == 200 )
        {
            $this->token = (string) $response->getBody();
            $_SESSION[self::session_token_key] = $this->token;
            return true;
        }

        $this->token = '';
        return false;
    }

    
    public function getZusagen()
    {
        return $this->zusagen;
    }

    public function getPosteingang()
    {
        return $this->posteingang;
    }


    public function readZusagen()
    {
        if( !$this->isLoggedIn() )
            return false;

        $response = NULL;
        try {

            $response = $this->client->get( '/v1/zusagen', [
                'headers' => ['Authorization' => 'Bearer ' . $this->token]
            ] );
    
        } catch (ClientException | ServerException $e) {                      
            error_log( $e->getMessage() );
            return false;
        }

        if( $response->getStatusCode() == 200 )
        {
            $body = (string) $response->getBody();
            $this->zusagen = json_decode( $body );
            return true;
        }

        return false;        
    }


    public function readPosteingang()
    {
        if( !$this->isLoggedIn() )
            return false;

        $response = NULL;
        try {

            $response = $this->client->get( '/v1/posteingang', [
                'headers' => ['Authorization' => 'Bearer ' . $this->token]
            ] );
    
        } catch (ClientException | ServerException $e) {                      
            error_log( $e->getMessage() );
            return false;
        }

        if( $response->getStatusCode() == 200 )
        {
            $body = (string) $response->getBody();
            $this->posteingang = json_decode( $body );
            return true;
        }

        return false;        
    }


    public function postBeitrag( String $ticket_id, String $text, array $files = NULL )
    {
        if( !$this->isLoggedIn() )
            return false;


        $multipart = array();
        $multipart[] = [
            'name'     => 'ticket_id',
            'contents' => $ticket_id
        ];
        $multipart[] = [
            'name'     => 'text',
            'contents' => $text
        ];
        
        if( isset( $files ) )
        {
            foreach( $files as $filepath )
                $multipart[] = [
                    'name'     => basename( $filepath ),
                    'contents' => fopen( $filepath, 'r' )
                ];
        }

      

        $response = NULL;
        try {
            $response = $this->client->request( 'POST', '/v1/beitrag', [
                'headers' => ['Authorization' => 'Bearer ' . $this->token],
                'multipart' => $multipart
            ] );
    
        } catch (ClientException | ServerException $e) {                      
            error_log( $e->getMessage() );
            return false;
        }

        if( $response->getStatusCode() == 200 )
        {
            $body = (string) $response->getBody();
            $this->posteingang = json_decode( $body );
            return true;
        }

        return false;                 

    }

}



?>