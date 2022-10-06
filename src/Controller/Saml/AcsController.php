<?php

declare(strict_types=1);

namespace pcak\BixieApi\Controller\Saml;

use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\XmlDSig\AbstractSignatureReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * @Route(path="/_saml/acs", name="saml_acs", defaults={"_scope" = "frontend", "_token_check" = false})
 * @ServiceTag("controller.service_arguments")
 */
class AcsController
{
    use SamlControllerTrait;

    public function __invoke(Request $request): Response
    {
        try {
            $bindingFactory = new BindingFactory();
            $binding = $bindingFactory->getBindingByRequest($request);
        } catch (\Exception) {
            throw new BadRequestHttpException();
        }

        $messageContext = new MessageContext();
        /** @var \LightSaml\Model\Protocol\Response $response */
        $response = $binding->receive($request, $messageContext);

        $certificate = $this->getIdPEntityDescriptor($request)
            ->getFirstIdpSsoDescriptor()
            ?->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING)
            ?->getCertificate()
        ;

        if (null === $certificate) {
            throw new InternalServerErrorHttpException('Missing SAML IdP certificate');
        }

        try {
            /** @var AbstractSignatureReader $signature */
            $signature = $response->getSignature();
            if (!$signature->validate(KeyHelper::createPublicKey($certificate))) {
                throw new \RuntimeException('Signature validation failed');
            }
        } catch (\Exception) {
            throw new BadRequestHttpException('SAML Signature validation failed');
        }

        dump($response);

        return new Response('SAML Successful');
    }
}
