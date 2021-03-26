<?php

namespace pcak\BixieApi\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;

/**
 * @Hook("processFormData")
 */
class BixieApiUpdatePosteingangFormListener
{
    const form_id = 'BixieApiUpdatePosteingangForm';

    private ?\pcak\BixieApi\ApiClient $client = null;



    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        if (isset($form->formID) && $form->formID == self::form_id) {            
            $this->update();
        }
    }

    private function update()
    {
        if (is_null($this->client)) {
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        } else {
            $this->client->updateFromSession();
        }

        $this->client->readPosteingang();
    }
}
