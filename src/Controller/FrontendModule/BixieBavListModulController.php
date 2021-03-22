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

class BixieBavListModulController extends AbstractFrontendModuleController
{
    private ?\pcak\BixieApi\ApiClient $client = null;


    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        if( is_null( $this->client ) )
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        else
            $this->client->updateFromSession();


        if( !$this->client->isLoggedIn() )
            return $template->getResponse();

        if( $this->client->needZusagenUpdate() )
            $this->client->readZusagen();

        if( $this->client->needZusagenUpdate() )
            return $template->getResponse();

        $z = $this->client->getZusagen();

        $template->vorname = $z->vorname;
        $template->name = $z->name;
        $template->username = $z->username;
        $template->zusagen = $z->zusagen;

        return $template->getResponse();
    }
}

?>