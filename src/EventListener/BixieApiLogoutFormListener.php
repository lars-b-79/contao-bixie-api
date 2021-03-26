<?php

namespace pcak\BixieApi\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use pcak\BixieApi\ApiClient;

/**
 * @Hook("processFormData")
 */
class BixieApiLogoutFormListener
{
    const form_id = 'BixieApiLogoutForm';


    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        if (isset($form->formID) && $form->formID == self::form_id) {
            unset($_SESSION[ApiClient::session_token_key]);
            unset($_SESSION[ApiClient::session_zusagen_key]);
            unset($_SESSION[ApiClient::session_posteingang_key]);
        }
    }
}
