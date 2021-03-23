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

class BixieBavModulController extends AbstractFrontendModuleController
{
    private ?\pcak\BixieApi\ApiClient $client = null;


    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $zusage_id = $request->query->get('zid');


        if (!isset($zusage_id)) {
            return $template->getResponse();
        }

        if (is_null($this->client)) {
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        } else {
            $this->client->updateFromSession();
        }


        if (!$this->client->isLoggedIn()) {
            return $template->getResponse();
        }

        if ($this->client->needZusagenUpdate()) {
            $this->client->readZusagen();
        }

        if ($this->client->needZusagenUpdate()) {
            return $template->getResponse();
        }



        $zl = $this->client->getZusagen();
        $template->vorname = $zl->vorname;
        $template->name = $zl->name;
        $template->username = $zl->username;
       

    
        foreach ($zl->zusagen as $z0) {
            if ($z0->id == $zusage_id) {
                $template->zusage = $z0;
                break;
            }
        }

        

        return $template->getResponse();
    }
}
