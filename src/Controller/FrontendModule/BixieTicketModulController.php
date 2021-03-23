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

class BixieTicketModulController extends AbstractFrontendModuleController
{
    private ?\pcak\BixieApi\ApiClient $client = null;


    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $prozess_id = $request->query->get('pid');


        if (!isset($prozess_id)) {
            return $template->getResponse();
        }

        if (is_null($this->client)) {
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        } else {
            $this->client->updateFromSession();
        }


        if ($this->client->needPosteingangUpdate()) {
            $this->client->readPosteingang();
        }

        if ($this->client->needPosteingangUpdate()) {
            return $template->getResponse();
        }

        $zl = $this->client->getPosteingang();
        $template->vorname = $zl->vorname;
        $template->name = $zl->name;
        $template->username = $zl->username;
       

    
        foreach ($zl->offen as $t0) {
            if ($t0->id == $prozess_id) {
                $template->ticket = $t0;
                break;
            }
        }

        foreach ($zl->geschlossen as $t0) {
            if ($t0->id == $prozess_id) {
                $template->ticket = $t0;
                break;
            }
        }
        

        return $template->getResponse();
    }
}
