<?php

namespace Networkteam\ImageProxy\Aspects;

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Flow\Annotations as Flow;
use Networkteam\ImageProxy\ImgproxyBuilder;

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

        // We only use imgproxy for images...
        if (!($asset instanceof Image)) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        /** @var ThumbnailConfiguration $configuration */
        $configuration = $joinPoint->getMethodArgument('configuration');
        /** @var ActionRequest $request */
        $request = $joinPoint->getMethodName('request');

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

        $url = $builder->buildUrl($sourceUri);

        if ($configuration->getQuality() !== null) {
            $url->quality($configuration->getQuality());
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

        $expectedSize = ImgproxyBuilder::expectedSize($asset->getWidth(), $asset->getHeight(), $targetWidth, $targetHeight, $resizingType, $enlarge);

        return [
            'width' => $expectedSize['width'],
            'height' => $expectedSize['height'],
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
