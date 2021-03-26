<?php declare(strict_types=1);
namespace pcak\BixieApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\Yaml\Yaml;

class ApiClient
{
    const base_url = 'https://api.bixie.cloud/v1/';
    //const base_url = 'https://staging-api.bixie.cloud/v1/';
    const session_token_key = 'bixie_api_token';
    const session_zusagen_key = 'bixie_api_zusagen';
    const session_posteingang_key = 'bixie_api_posteingang';

    private \GuzzleHttp\Client $client;
    private String $token = '';
    private $zusagen;
    private $posteingang;
  


    public static function getApiUrl()
    {
        $rootDir = \System::getContainer()->getParameter('kernel.project_dir');
        $parameter_file_path = $rootDir . '/config/parameters.yml';

        $replace = (PHP_OS_FAMILY === "Windows") ? '\\' : '/';
        $parameter_file_path = str_replace( ['\\', '/'], $replace, $parameter_file_path);

        
        if( file_exists( $parameter_file_path ) )
        {
            $parameters = Yaml::parse( file_get_contents($parameter_file_path));

            if( array_key_exists( 'bixie-api-base-url', $parameters['parameters'] ) )
                return $parameters['parameters']['bixie-api-base-url'];
        }

        return self::base_url;
    }


    public static function withConfiguredUrl()
    {
        $url = self::getApiUrl();
      
        $instance = new self();
        $instance->client = new \GuzzleHttp\Client(['base_uri' => $url]);

        if (!isset($_SESSION)) {
            $_SESSION = array();
        }


        if (isset($_SESSION[self::session_token_key])) {
            $instance->token = $_SESSION['bixie_api_token'];
        }

        if (isset($_SESSION[self::session_zusagen_key])) {
            $instance->zusagen = $_SESSION[self::session_zusagen_key];
        }

        if (isset($_SESSION[self::session_posteingang_key])) {
            $instance->posteingang = $_SESSION[self::session_posteingang_key];
        }


        return $instance;
    }

    public static function withTestHandler($handlerStack)
    {
        $instance = new self();
        $instance->client = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        if (!isset($_SESSION)) {
            $_SESSION = array();
        }

        if (isset($_SESSION[self::session_token_key])) {
            $instance->token = $_SESSION[self::session_token_key];
        }

        if (isset($_SESSION[self::session_zusagen_key])) {
            $instance->zusagen = $_SESSION[self::session_zusagen_key];
        }

        if (isset($_SESSION[self::session_posteingang_key])) {
            $instance->posteingang = $_SESSION[self::session_posteingang_key];
        }


        return $instance;
    }

 
    public function updateFromSession()
    {
        if (!isset($_SESSION)) {
            $_SESSION = array();
        }

        if (isset($_SESSION[self::session_token_key])) {
            $instance->token = $_SESSION[self::session_token_key];
        }

        if (isset($_SESSION[self::session_zusagen_key])) {
            $instance->zusagen = $_SESSION[self::session_zusagen_key];
        }

        if (isset($_SESSION[self::session_posteingang_key])) {
            $instance->posteingang = $_SESSION[self::session_posteingang_key];
        }
    }


    public function isLoggedIn()
    {
        return !empty($this->token);
    }

    public function login(String $username, String $password)
    {
        $body = array(
            'username'    =>  $username,
            'password'    =>  $password );

       
        $response = null;
        try {
            $response = $this->client->post('/v1/token', [
                'body' => json_encode($body),
                'headers' => ['Content-Type' => 'application/json']
            ]);
        } catch (ClientException | ServerException $e) {
            error_log($e->getMessage());
            $this->token = '';
            return false;
        }
     
        if ($response->getStatusCode() == 200) {
            $this->token = (string) $response->getBody();
            $_SESSION[self::session_token_key] = $this->token;
            return true;
        }

        $this->token = '';
        return false;
    }


    public function needZusagenUpdate()
    {
        return !isset($this->zusagen);
    }

    public function needPosteingangUpdate()
    {
        return !isset($this->posteingang);
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
        if (!$this->isLoggedIn()) {
            return false;
        }

        $response = null;
        try {
            $response = $this->client->get('/v1/zusagen', [
                'headers' => ['Authorization' => 'Bearer ' . $this->token]
            ]);
        } catch (ClientException | ServerException $e) {
            error_log($e->getMessage());
            return false;
        }

        if ($response->getStatusCode() == 200) {
            $body = (string) $response->getBody();
            $this->zusagen = json_decode($body);
            return true;
        }

        return false;
    }


    public function readPosteingang()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $response = null;
        try {
            $response = $this->client->get('/v1/posteingang', [
                'headers' => ['Authorization' => 'Bearer ' . $this->token]
            ]);
        } catch (ClientException | ServerException $e) {
            error_log($e->getMessage());
            return false;
        }

        if ($response->getStatusCode() == 200) {
            $body = (string) $response->getBody();
            $this->posteingang = json_decode($body);
            return true;
        }

        return false;
    }


    public function postBeitrag(String $ticket_id, String $text, array $files = null)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }


        $multipart = array();
        $multipart[] = [
            'name'     => 'ticket_id',
            'contents' => $ticket_id
        ];
        $multipart[] = [
            'name'     => 'text',
            'contents' => $text
        ];
        
        if (isset($files)) {
            foreach ($files as $fileDescriptor) {
                $multipart[] = [
                    'name'     => $fileDescriptor['name'],
                    'filename'     => $fileDescriptor['name'],
                    'contents' => fopen($fileDescriptor['path'], 'r')
                ];
            }
        }

      

        $response = null;
        try {
            $response = $this->client->request('POST', '/v1/beitrag', [
                'headers' => ['Authorization' => 'Bearer ' . $this->token],
                'multipart' => $multipart
            ]);
        } catch (ClientException | ServerException $e) {
            error_log($e->getMessage());
            return false;
        }

        if ($response->getStatusCode() == 200) {
            $body = (string) $response->getBody();
            $this->posteingang = json_decode($body);
            return true;
        }

        return false;
    }
}
