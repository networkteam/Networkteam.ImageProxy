<?php

namespace Networkteam\ImageProxy\Eel;

/***************************************************************
 *  (c) 2023 networkteam GmbH - all rights reserved
 ***************************************************************/

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Networkteam\ImageProxy\ImgproxyBuilder;

class UrlHelper implements ProtectedContextAwareInterface
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

    public function createUrl(
        string $sourceUrl,
        $width = null,
        $height = null,
        $maximumWidth = null,
        $maximumHeight = null,
        $allowCropping = false,
        $allowUpScaling = false,
        $quality = null,
        $format = null
    ) {
        $configuration = new ThumbnailConfiguration(
            $width,
            $maximumWidth,
            $height,
            $maximumHeight,
            $allowCropping,
            $allowUpScaling,
            false, // async
            $quality,
            $format
        );

        $builder = new ImgproxyBuilder(
            $this->settings['imgproxyUrl'],
            $this->settings['key'],
            $this->settings['salt']
        );
        $url = $builder->buildUrl($sourceUrl);

        $targetHeight = $configuration->getHeight() ?? $configuration->getMaximumHeight() ?? 0;
        $targetWidth = $configuration->getWidth() ?? $configuration->getMaximumWidth() ?? 0;

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

        if ((bool)$this->settings['autoFormat'] === false && $configuration->getFormat() !== null) {
            $url->extension($configuration->getFormat());
        }

        return $url->build();
    }

    /**
     * All methods are considered safe, i.e. can be executed from within Eel
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
