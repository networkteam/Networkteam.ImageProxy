<?php

namespace Networkteam\ImageProxy\Aspects;

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\MediaTypes;
use Networkteam\ImageProxy\ImgproxyBuilder;

/**
 * @Flow\Aspect
 */
class PublicPackageResourceUriAspect
{
    /**
     * @Flow\InjectConfiguration(package="Networkteam.ImageProxy")
     * @var array
     */
    protected $settings;

    /**
     * @Flow\InjectConfiguration(package="Neos.Media.image.defaultOptions.quality")
     * @var integer
     */
    protected $defaultQuality;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Around("method(Neos\Flow\ResourceManagement\ResourceManager->getPublicPackageResourceUri())")
     */
    public function generateImgproxyUri(JoinPointInterface $joinPoint): string
    {
        $sourceUrl = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if (empty($this->settings['imgproxyUrl'])
            || ($this->settings['staticResources']['enabled'] ?? false) === false
            || preg_match($this->settings['staticResources']['ignoreRegexp'], $sourceUrl) === 1
        ) {
            return $sourceUrl;
        }

        $filename = pathinfo($sourceUrl, PATHINFO_BASENAME);
        $mediaType = MediaTypes::getMediaTypeFromFilename($filename);

        if (($this->settings['mediaTypes'][$mediaType]['enabled'] ?? false) === false) {
            return $sourceUrl;
        }

        $builder = new ImgproxyBuilder(
            $this->settings['imgproxyUrl'],
            $this->settings['key'],
            $this->settings['salt']
        );

        $url = $builder->buildUrl($sourceUrl);
        if (!empty($this->settings['formatQuality'])) {
            $url->formatQuality($this->settings['formatQuality']);
        } else {
            // if no settings are provided use neos.media image default quality
            $url->quality($this->defaultQuality);
        }

        $stat = stat(sprintf(
            'resource://%s/Public/%s',
            $joinPoint->getMethodArgument('packageKey'),
            $joinPoint->getMethodArgument('relativePathAndFilename'),
        ));
        if ($stat) {
            $url->cacheBuster($stat['mtime']);
        } else {
            return $sourceUrl;
        }

        return $url->build();
    }
}
