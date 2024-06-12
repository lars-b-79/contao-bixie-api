<?php

namespace pcak\BixieApi\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use pcak\BixieApi\LoginStatusCookie;

/**
 * @Hook("processFormData")
 */
class BixieApiLoginFormListener
{
    const form_id = 'BixieApiLoginForm';
    const field_username = 'username';
    const field_password = 'password';

    private ?\pcak\BixieApi\ApiClient $client = null;



    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        if (isset($form->formID) && $form->formID == self::form_id) {
            $this->login($submittedData[ self::field_username ], $submittedData[ self::field_password ]);
        }
    }

    private function login(string $username, string $password)
    {
        if (is_null($this->client)) {
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        } else {
            $this->client->updateFromSession();
        }
        
        $result_status = true;
        if ($this->client->login($username, $password) == false) {
            $result_status = false;
        }
       
        LoginStatusCookie::set($result_status, $this->client->isOnboarding() );
    }
}
