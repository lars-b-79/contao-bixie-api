<?php

namespace pcak\BixieApi\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;

/**
 * @Hook("processFormData")
 */
class BixieApiPostBeitragFormListener
{
    const form_id = 'BixieApiPostBeitragForm';
    
    const field_text   = 'text';
    const field_upload = 'upload';

    private ?\pcak\BixieApi\ApiClient $client = null;



    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        if (isset($form->formID) && $form->formID == self::form_id) {
            $ticket_id = \Input::get('pid');

            if (isset($ticket_id)) {
                $this->post($ticket_id, $submittedData[ self::field_text ], $files[ self::field_upload ]);
            }
        }
    }

    private function post(string $ticket_id, string $text, ?array $files)
    {
        if (is_null($this->client)) {
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        } else {
            $this->client->updateFromSession();
        }

        $postFiles = array();

        if (isset($files)) {
            error_log($files['name'] . ", " . $files['tmp_name']);
            array_push($postFiles, [ 'name' => $files['name'], 'path' => $files['tmp_name']]);
        }
        $this->client->postBeitrag($ticket_id, $text, $postFiles);
    }
}
