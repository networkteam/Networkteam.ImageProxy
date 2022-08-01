<?php

namespace Networkteam\ImageProxy\Aspects;

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Flow\Annotations as Flow;
use Networkteam\ImageProxy\ImgproxyBuilder;
use Networkteam\ImageProxy\Model\Dimensions;

/**
 * @Flow\Aspect
 */
class ThumbnailAspect
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
     * @Flow\Around("method(Neos\Media\Domain\Service\AssetService->getThumbnailUriAndSizeForAsset())")
     */
    public function generateImgproxyUri(JoinPointInterface $joinPoint): ?array
    {
        /** @var Asset $asset */
        $asset = $joinPoint->getMethodArgument('asset');
        $mediaType = $asset->getResource()->getMediaType();

        // We only use imgproxy for images...
        if (!($asset instanceof Image || $asset instanceof ImageVariant)
            || empty($this->settings['imgproxyUrl'])
            || ($this->settings['mediaTypes'][$mediaType]['enabled'] ?? false) === false
        ) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        /** @var ThumbnailConfiguration $configuration */
        $configuration = $joinPoint->getMethodArgument('configuration');

        $builder = new ImgproxyBuilder(
            $this->settings['imgproxyUrl'],
            $this->settings['key'],
            $this->settings['salt']
        );

        $sourceUri = '';

        $resourceCollection = $this->resourceManager->getCollection($asset->getResource()->getCollectionName());
        $resourceStorage = $resourceCollection->getStorage();
        if (get_class($resourceStorage) === 'Flownative\Aws\S3\S3Storage') {
            $bucketName = $resourceStorage->getBucketName();
            $keyPrefix = $resourceStorage->getKeyPrefix();
            $sourceUri = sprintf('s3://%s/%s/%s', $bucketName, rtrim($keyPrefix, '/'), $asset->getResource()->getSha1());
        } else {
            $sourceUri = $this->resourceManager->getPublicPersistentResourceUri($asset->getResource());
        }

        $targetHeight = $configuration->getHeight() ?? $configuration->getMaximumHeight() ?? 0;
        $targetWidth = $configuration->getWidth() ?? $configuration->getMaximumWidth() ?? 0;

        $targetDimension = new Dimensions($targetWidth, $targetHeight);

        $url = $builder->buildUrl($sourceUri);
        $url->fileName(pathinfo($asset->getResource()->getFilename(), PATHINFO_FILENAME));

        // set the quality information if given
        // otherwise use the format quality string if provided
        if ($configuration->getQuality() !== null) {
            $url->quality($configuration->getQuality());
        } else {
            if (!empty($this->settings['formatQuality'])) {
                $url->formatQuality($this->settings['formatQuality']);
            } else {
                // if no settings are provided use neos.media image default quality
                $url->quality($this->defaultQuality);
            }
        }

        $resizingType = ImgproxyBuilder::RESIZE_TYPE_FIT;

        // TODO What if only one of maximum width / height and respective height / width are set?
        if ($configuration->isCroppingAllowed()) {
            $resizingType = ImgproxyBuilder::RESIZE_TYPE_FILL;
        } else if ($configuration->getMaximumWidth() === null && $configuration->getWidth() !== null && $configuration->getMaximumHeight() && $configuration->getHeight() !== null) {
            $resizingType = ImgproxyBuilder::RESIZE_TYPE_FORCE;
        }

        $enlarge = $configuration->isUpScalingAllowed();

        $url->resize($resizingType, $targetWidth, $targetHeight, $enlarge, false);

        // $url->options()->withStripMetadata();
        // $url->options()->withStripColorProfile();

        if ((bool)$this->settings['autoFormat'] === false && $configuration->getFormat() !== null) {
            $url->extension($configuration->getFormat());
        }

        $actualDimension = new Dimensions($asset->getWidth(), $asset->getHeight());

        $expectedSize = ImgproxyBuilder::expectedSize($actualDimension, $targetDimension, $resizingType, $enlarge);

        return [
            'width' => $expectedSize->getWidth(),
            'height' => $expectedSize->getHeight(),
            'src' => $url->build()
        ];
    }

    protected function getAsset(JoinPointInterface $joinPoint): Asset
    {
        return $joinPoint->getMethodArgument('asset');
    }

    protected function getThumbnailConfiguration(JoinPointInterface $joinPoint): ThumbnailConfiguration
    {
        return $joinPoint->getMethodArgument('configuration');
    }

    protected function getRequest(JoinPointInterface $joinPoint): ActionRequest
    {
        return $joinPoint->getMethodArgument('request');
    }
}
