<?php

namespace pcak\BixieApi\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;

/**
 * @Hook("processFormData")
 */
class BixieApiOpenTicketFormListener
{
    const form_id = 'BixieApiOpenTicketForm';
    
    const field_betreff = 'betreff';
    const field_text    = 'text';
    const field_upload  = 'upload';

    private ?\pcak\BixieApi\ApiClient $client = null;



    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        if (isset($form->formID) && $form->formID == self::form_id) {            
            $this->post($submittedData[ self::field_betreff ], $submittedData[ self::field_text ], $files[ self::field_upload ]);
        }
    }

    private function post(string $betreff, string $text, ?array $files)
    {
        if (is_null($this->client)) {
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        } else {
            $this->client->updateFromSession();
        }

        $postFiles = array();

        if (isset($files)) {
            array_push($postFiles, [ 'name' => $files['name'], 'path' => $files['tmp_name']]);
        }

        error_log( "open ticket" );
        $this->client->openTicket($betreff, $text, $postFiles);
    }
}
