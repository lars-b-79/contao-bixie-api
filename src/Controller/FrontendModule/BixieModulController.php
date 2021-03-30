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

class BixieModulController extends AbstractFrontendModuleController
{
    private ?\pcak\BixieApi\ApiClient $client = null;

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        if (is_null($this->client)) {
            $this->client = \pcak\BixieApi\ApiClient::withConfiguredUrl();
        } else {
            $this->client->updateFromSession();
        }

        $template->loginStatus = $this->client->isLoggedIn();

        if (!$this->client->isLoggedIn()) {
            return $template->getResponse();
        }

        if ($this->client->needZusagenUpdate()) {
            $this->client->readZusagen();
        }

        if ($this->client->needPosteingangUpdate()) {
            $this->client->readPosteingang();
        }


        $posteingang = $this->client->getPosteingang();
        $template->vorname = $posteingang->vorname;
        $template->name = $posteingang->name;
        $template->username = $posteingang->username;
        $template->offen = $posteingang->offen;
        $template->geschlossen = $posteingang->geschlossen;

        $zusagen = $this->client->getZusagen()->zusagen;
        $template->zusagen = $zusagen;


        $zusage_id = $request->query->get('zid');
        if (isset($zusage_id)) {
            foreach ($zusagen as $z0) {
                if ($z0->id == $zusage_id) {
                    $template->selectedZusage = $z0;
                    break;
                }
            }
        }



        $prozess_id = $request->query->get('pid');
        if (isset($prozess_id)) {
            foreach ($posteingang->offen as $t0) {
                if ($t0->id == $prozess_id) {
                    $template->selectedTicket = $t0;
                    break;
                }
            }
    
            foreach ($posteingang->geschlossen as $t0) {
                if ($t0->id == $prozess_id) {
                    $template->selectedTicket = $t0;
                    break;
                }
            }
        }


        return $template->getResponse();
    }
}
