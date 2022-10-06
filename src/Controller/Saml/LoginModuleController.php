<?php

declare(strict_types=1);

namespace pcak\BixieApi\Controller\Saml;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use LightSaml\Binding\BindingFactory;
use LightSaml\Binding\SamlPostResponse;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginModuleController extends AbstractFrontendModuleController
{
    use SamlControllerTrait;

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $idpEntityDescriptor = $this->getIdPEntityDescriptor($request);
        $idpSsoDescriptor = $idpEntityDescriptor->getFirstIdpSsoDescriptor();

        $targetUrl = $idpSsoDescriptor
            ?->getFirstSingleSignOnService(SamlConstants::BINDING_SAML2_HTTP_REDIRECT)
            ?->getLocation()
        ;

        if (null === $targetUrl) {
            throw new \RuntimeException('No SAML HTTP-Redirect location in IdP Metadata');
        }

        $authnRequest = new AuthnRequest();
        $authnRequest
            ->setAssertionConsumerServiceURL($this->getOwnAssertionConsumerServiceURL())
            ->setProtocolBinding(SamlConstants::BINDING_SAML2_HTTP_POST)
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($targetUrl)
            ->setIssuer(new Issuer($this->getOwnEntityId($request)))
        ;

        if ($idpSsoDescriptor?->getWantAuthnRequestsSigned()) {
            $credentials = $this->getOwnCredential($request);
            $authnRequest->setSignature(
                new SignatureWriter($credentials->getCertificate(), $credentials->getPrivateKey())
            );
        }

        $bindingFactory = new BindingFactory();
        $redirectBinding = $bindingFactory->create(SamlConstants::BINDING_SAML2_HTTP_POST);

        $messageContext = new MessageContext();
        $messageContext->setMessage($authnRequest);

        /** @var SamlPostResponse $httpResponse */
        $httpResponse = $redirectBinding->send($messageContext);

        $template->destination = $httpResponse->getDestination();
        $template->data = $httpResponse->getData();

        return $template->getResponse();
    }
}
