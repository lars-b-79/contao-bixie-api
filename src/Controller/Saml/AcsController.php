<?php

declare(strict_types=1);

namespace pcak\BixieApi\Controller\Saml;

use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Assertion\EncryptedAssertionReader;
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
        $binding->receive($request, $messageContext);

        $response = $messageContext->getMessage();

        if (!$response instanceof \LightSaml\Model\Protocol\Response) {
            throw new BadRequestHttpException('No response message');
        }

        $assertion = $response->getFirstAssertion();

        if (null === $assertion) {
            $decryptDeserializeContext = new \LightSaml\Model\Context\DeserializationContext();
            $credentials = $this->getOwnCredential($request);

            /** @var EncryptedAssertionReader $reader */
            $reader = $response->getFirstEncryptedAssertion();
            $assertion = $reader->decryptMultiAssertion([$credentials], $decryptDeserializeContext);
        }

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
            $signature = $assertion->getSignature();
            if (!$signature->validate(KeyHelper::createPublicKey($certificate))) {
                throw new \RuntimeException('Signature validation failed');
            }
        } catch (\Exception) {
            throw new BadRequestHttpException('SAML Signature validation failed');
        }

        dump($assertion);

        return new Response('SAML Successful');
    }
}
