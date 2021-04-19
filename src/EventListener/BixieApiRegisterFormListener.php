<?php

namespace pcak\BixieApi\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;

/**
 * @Hook("processFormData")
 */
class BixieApiRegisterFormListener
{
    const form_id = 'BixieApiRegisterForm';
    const field_email = 'email';

    private ?\pcak\BixieApi\ApiClient $client = null;



    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        if (isset($form->formID) && $form->formID == self::form_id) {
            $this->register($submittedData[ self::field_email ]);
        }
    }

    private function register(string $email)
    {
        if (is_null($this->client)) {
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        } else {
            $this->client->updateFromSession();
        }

        $this->client->register($email);
    }
}
