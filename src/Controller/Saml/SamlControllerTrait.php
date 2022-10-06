<?php

declare(strict_types=1);

namespace pcak\BixieApi\Controller\Saml;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\PageModel;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\EntityDescriptor;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait SamlControllerTrait
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Filesystem $filesystem,
        private readonly string $projectDir,
    ){
    }

    /**
     * @var PageModel[]
     */
    private array $rootPages = [];

    protected function getRootPage(Request $request): PageModel
    {
        $host = $request->getHost();

        if (isset($this->rootPages[$host])) {
            return $this->rootPages[$host];
        }

        $this->framework->initialize(true);

        $page = PageModel::findPublishedFallbackByHostname(
            $request->getHost(),
            ['fallbackToEmpty' => true]
        );

        if (null === $page || !$page->samlSPEnabled) {
            throw new NotFoundHttpException();
        }

        return $this->rootPages[$host] = $page;
    }

    protected function getIdPEntityDescriptor(Request $request): EntityDescriptor
    {
        $rootPage = $this->getRootPage($request);

        $entityDescriptor = new EntityDescriptor();

        $serializationContext = new DeserializationContext();
        $serializationContext->getDocument()->loadXML($rootPage->samlIdPMetadata);
        $entityDescriptor->deserialize($serializationContext->getDocument(), $serializationContext);

        return $entityDescriptor;
    }

    protected function getOwnAssertionConsumerServiceURL(): string
    {
        return $this->urlGenerator->generate('saml_acs', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    protected function getOwnCredential(Request $request): X509Credential
    {
        $rootPage = $this->getRootPage($request);

        $credential = new X509Credential(
            $this->getOwnCertificate($rootPage),
            $this->getOwnPrivateKey($rootPage)
        );

        $credential->setEntityId($this->getOwnEntityId($request));

        return $credential;
    }

    protected function getOwnEntityId(Request $request): string
    {
        return $request->getSchemeAndHttpHost().'/_saml';
    }

    private function getOwnCertificate(PageModel $rootPage): X509Certificate
    {
        $file = FilesModel::findByPk($rootPage->samlSPCertificate);

        if (null === $file
            || !$this->filesystem->exists($path = Path::join($this->projectDir, $file->path))
        ) {
            throw new \RuntimeException('SP Certificate not found');
        }

        return X509Certificate::fromFile($path);
    }

    private function getOwnPrivateKey(PageModel $rootPage): XMLSecurityKey
    {
        $file = FilesModel::findByPk($rootPage->samlSPPrivateKey);

        if (null === $file
            || !$this->filesystem->exists($path = Path::join($this->projectDir, $file->path))
        ) {
            throw new \RuntimeException('SP Private Key not found');
        }

        return KeyHelper::createPrivateKey($path, null, true);
    }
}
