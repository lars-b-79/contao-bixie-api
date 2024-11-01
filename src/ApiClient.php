<?php declare(strict_types=1);
namespace pcak\BixieApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\Yaml\Yaml;

class ApiClient
{
    const base_url = 'https://api.bixie.de/brp/v1/';
    //const base_url = 'https://staging-api.bixie.cloud/v1/';
    const session_token_key = 'bixie_api_token';
    const session_zusagen_key = 'bixie_api_zusagen';
    const session_posteingang_key = 'bixie_api_posteingang';

    private \GuzzleHttp\Client $client;
    private String $token = '';    
    private String $token_b64 = '';    
    private $onboarding = False;
    private $zusagen;
    private $posteingang;
    
  

    



    public static function getApiUrl()
    {
        $rootDir = \System::getContainer()->getParameter('kernel.project_dir');
        $parameter_file_path = $rootDir . '/config/parameters.yml';

        $replace = (PHP_OS_FAMILY === "Windows") ? '\\' : '/';
        $parameter_file_path = str_replace(['\\', '/'], $replace, $parameter_file_path);

        
        if (file_exists($parameter_file_path)) {
            $parameters = Yaml::parse(file_get_contents($parameter_file_path));

            if (array_key_exists('bixie-api-base-url', $parameters['parameters'])) {
                return $parameters['parameters']['bixie-api-base-url'];
            }
        }

        return self::base_url;
    }

    public static function getJwtSecret()
    {
        $rootDir = \System::getContainer()->getParameter('kernel.project_dir');
        $parameter_file_path = $rootDir . '/config/parameters.yml';

        $replace = (PHP_OS_FAMILY === "Windows") ? '\\' : '/';
        $parameter_file_path = str_replace(['\\', '/'], $replace, $parameter_file_path);

        
        if ( !file_exists($parameter_file_path))
            return null;


        $parameters = Yaml::parse(file_get_contents($parameter_file_path));

        if ( !array_key_exists('jwt-secret', $parameters['parameters']))
            return null;
       
        return $parameters['parameters']['jwt-secret'];
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
            $instance->token = $_SESSION[self::session_token_key];
            $instance->token_b64 = base64_encode( $instance->token );
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
            $instance->token_b64 = base64_encode( $instance->token );
        }

        if (isset($_SESSION[self::session_zusagen_key])) {
            $instance->zusagen = $_SESSION[self::session_zusagen_key];
        }

        if (isset($_SESSION[self::session_posteingang_key])) {
            $instance->posteingang = $_SESSION[self::session_posteingang_key];
        }


        return $instance;
    }

    public function constructJwt( $pn, $email, $host, $secret )
    {
        $headers = array('alg'=>'HS256','typ'=>'JWT');
        $payload = array('pn'=>$pn,'email'=>$email, 'host'=>$host, 'exp'=>(time() + 3600));
        
        $headers_encoded = $this->base64url_encode(json_encode($headers));	
	    $payload_encoded = $this->base64url_encode(json_encode($payload));
	
	    $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
	    $signature_encoded = $this->base64url_encode($signature);	
	    return  "$headers_encoded.$payload_encoded.$signature_encoded";
    }


    public function constructJwtUsername( $username, $email, $host, $secret )
    {
        $headers = array('alg'=>'HS256','typ'=>'JWT');
        $payload = array('external_username'=>$username,'email'=>$email, 'host'=>$host, 'exp'=>(time() + 3600));
        
        $headers_encoded = $this->base64url_encode(json_encode($headers));	
	    $payload_encoded = $this->base64url_encode(json_encode($payload));
	
	    $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
	    $signature_encoded = $this->base64url_encode($signature);	
	    return  "$headers_encoded.$payload_encoded.$signature_encoded";
    }

    public function clear()
    {
        $this->token = '';
        $this->token_b64 = '';
        $this->zusagen = null;
        $this->posteingang = null;
    }
    

    private function base64url_encode( $str ) 
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    public function tokenFromParameter( $pn, $email, $host )
    {
        $secret = $this->getJwtSecret();        
	    $this->token = $this->constructJwt( $pn, $email, $host, $secret );
        $this->token_b64 = base64_encode( $this->token );
    }


    public function tokenFromParameterUsername( $username, $email, $host )
    {
        $secret = $this->getJwtSecret();        
	    $this->token = $this->constructJwtUsername( $username, $email, $host, $secret );
        $this->token_b64 = base64_encode( $this->token );
    }



    

    public function getToken()
    {
        return $this->token;
    }

    public function getToken_b64()
    {
        return $this->token_b64;
    }

  
    public function updateFromSession()
    {
        if (!isset($_SESSION)) {
            $_SESSION = array();
        }

        if (isset($_SESSION[self::session_token_key])) {
            $this->token = $_SESSION[self::session_token_key];
            $this->token_b64 = base64_encode( $this->token );
        }

        if (isset($_SESSION[self::session_zusagen_key])) {
            $this->zusagen = $_SESSION[self::session_zusagen_key];
        }

        if (isset($_SESSION[self::session_posteingang_key])) {
            $this->posteingang = $_SESSION[self::session_posteingang_key];
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

       
        $this->onboarding = False;
        $response = null;
        $statusCode = 0;
        try {
            $response = $this->client->post('/brp/v1/token', [
                'body' => json_encode($body),
                'headers' => ['Content-Type' => 'application/json']
            ]);
            $statusCode = $response->getStatusCode();
        } catch (ClientException | ServerException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            error_log($e->getMessage());
            $this->token = '';


            if ($statusCode == 401) {
                $this->onboarding = True;
            }           

            return false;
        }
     
        if ($statusCode == 200) {
            $this->token = (string) $response->getBody();
            $this->token_b64 = base64_encode( $this->token );
            $_SESSION[self::session_token_key] = $this->token;
            return true;
        }


        if ($statusCode == 401) {
            $this->onboarding = True;
        }

        $this->token = '';
        return false;
    }


    public function register(String $email)
    {
        $body = array( 'email'    =>  $email );

       
        $response = null;
        try {
            $response = $this->client->post('/brp/v1/register', [
                'body' => json_encode($body),
                'headers' => ['Content-Type' => 'application/json']
            ]);
        } catch (ClientException | ServerException $e) {
            error_log($e->getMessage());
            return false;
        }
     
        return true;
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

    public function isOnboarding()
    {
        return $this->onboarding;
    }


    public function readZusagen()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $response = null;
        try {
            $response = $this->client->get('/brp/v1/zusagen', [
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
            $response = $this->client->get('/brp/v1/posteingang', [
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
            $response = $this->client->request('POST', '/brp/v1/beitrag', [
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



    
    public function openTicket(String $betreff, String $text, array $files = null)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }


        $multipart = array();
        $multipart[] = [
            'name'     => 'betreff',
            'contents' => $betreff
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
            $response = $this->client->request('POST', '/brp/v1/ticket', [
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
