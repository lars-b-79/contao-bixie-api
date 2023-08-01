<?php

declare(strict_types=1);

namespace pcak\BixieApi\Controller\Saml;

use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\EncryptedAssertionReader;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\XmlDSig\AbstractSignatureReader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use Symfony\Component\Yaml\Yaml;

/**
 * @Route(path="/_saml/acs", name="saml_acs", defaults={"_scope" = "frontend", "_token_check" = false})
 * @ServiceTag("controller.service_arguments")
 */
class AcsController
{
    use SamlControllerTrait;



    public static function logAssertions()
    {
        $rootDir = \System::getContainer()->getParameter('kernel.project_dir');
        $parameter_file_path = $rootDir . '/config/parameters.yml';

        $replace = (PHP_OS_FAMILY === "Windows") ? '\\' : '/';
        $parameter_file_path = str_replace(['\\', '/'], $replace, $parameter_file_path);

        
        if ( !file_exists($parameter_file_path))
            return false;


        $parameters = Yaml::parse(file_get_contents($parameter_file_path));

        if ( !array_key_exists('log-assertions', $parameters['parameters']))
            return false;
       
        return $parameters['parameters']['log-assertions'];
    }





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

            if ($reader instanceof EncryptedAssertionReader) {
                $assertion = $reader->decryptMultiAssertion([$credentials], $decryptDeserializeContext);
            }
        }

        if (null === $assertion) {
            throw new \RuntimeException('SAML message does not contain a valid assertion');
        }

        $idpEntityDescriptor = $this->getIdPEntityDescriptor($request);

        if ($assertion->getIssuer()->getValue() !== $idpEntityDescriptor->getEntityID()) {
            throw new \RuntimeException('SAML: invalid assertion issuer');
        }

        $certificate = $idpEntityDescriptor
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

        if (null !== ($notBefore = $assertion->getConditions()?->getNotBeforeDateTime())
            && $notBefore > new \DateTime()
        ) {
            throw new BadRequestHttpException('SAML Assertion not active');
        }

        if (null !== ($notAfter = $assertion->getConditions()?->getNotOnOrAfterDateTime())
            && $notAfter < new \DateTime()
        ) {
            throw new BadRequestHttpException('SAML Assertion expired');
        }


        $logMe = self::logAssertions();
      

        $attributes = [];
        if( null !== $assertion->getSubject() )
        {
            $nameId = $assertion->getSubject()->getNameID();
            $attributes['NameID'] = $nameId->getValue();

            if( $logMe )
                error_log( var_export( $nameId, true ) );            
        }


       
        foreach ($assertion->getAllItems() as $item) {
            if (!$item instanceof AttributeStatement) {
                continue;
            }

            if( $logMe )
                error_log( var_export( $item, true ) );

            foreach ($item->getAllAttributes() as $attribute) {
                $attributes[$attribute->getName()] = $attribute->getFirstAttributeValue();
            }
        }

        $request->getSession()->set('_saml_attributes', $attributes);

        return new RedirectResponse($response->getRelayState() . "#bav-konto" );
    }
}
