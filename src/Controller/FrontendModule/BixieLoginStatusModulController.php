<?php

namespace pcak\BixieApi\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BixieLoginStatusModulController extends AbstractFrontendModuleController
{
    private ?\pcak\BixieApi\ApiClient $client = null;
    const zusage_id_key = 'bixie_zusage_id';

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        if( is_null( $this->client ) )
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        else
            $this->client->updateFromSession();

        $template->loginStatus = $this->client->isLoggedIn() ? 'eingeloggt' :  'nicht eingeloggt';         
        $template->selected = $_SESSION[self::zusage_id_key];

        return $template->getResponse();
    }
}

?>