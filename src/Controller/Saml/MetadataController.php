<?php

declare(strict_types=1);

namespace pcak\BixieApi\Controller\Saml;

use LightSaml\Helper;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * @Route(path="/_saml/metadata", name="saml_metadata", defaults={"_scope" = "frontend", "_token_check" = false})
 * @ServiceTag("controller.service_arguments")
 */
class MetadataController
{
    use SamlControllerTrait;

    public function __invoke(Request $request): Response
    {
        $credentials = $this->getOwnCredential($request);

        $entityDescriptor = new EntityDescriptor();
        $entityDescriptor
            ->setID(Helper::generateID())
            ->setEntityID($this->getOwnEntityId($request))
        ;

        $entityDescriptor->addItem(
            $spSsoDescriptor = (new SpSsoDescriptor())
                ->setWantAssertionsSigned(true)
        );

        $spSsoDescriptor->addKeyDescriptor(
            new KeyDescriptor(KeyDescriptor::USE_SIGNING, $credentials->getCertificate())
        );
        $spSsoDescriptor->addKeyDescriptor(
            new KeyDescriptor(KeyDescriptor::USE_ENCRYPTION, $credentials->getCertificate())
        );

        $acsUrl = $this->getOwnAssertionConsumerServiceURL();
        $spSsoDescriptor->addAssertionConsumerService(
            (new AssertionConsumerService($acsUrl, SamlConstants::BINDING_SAML2_HTTP_POST))
                ->setIsDefault(true)
        );
        $spSsoDescriptor->addAssertionConsumerService(
            new AssertionConsumerService($acsUrl, SamlConstants::BINDING_SAML2_HTTP_REDIRECT)
        );
        $spSsoDescriptor->addSingleLogoutService(
            (new SingleLogoutService($acsUrl, SamlConstants::BINDING_SAML2_HTTP_POST))
        );
        $spSsoDescriptor->addSingleLogoutService(
            (new SingleLogoutService($acsUrl, SamlConstants::BINDING_SAML2_HTTP_REDIRECT))
        );

        $spSsoDescriptor->addNameIDFormat(SamlConstants::NAME_ID_FORMAT_EMAIL);

        $serializationContext = new SerializationContext();
        $entityDescriptor->serialize($serializationContext->getDocument(), $serializationContext);

        return new Response($serializationContext->getDocument()->saveXML(), Response::HTTP_OK, [
           'Content-Type' =>  'application/xml'
        ]);
    }
}
