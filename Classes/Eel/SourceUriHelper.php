<?php

namespace Networkteam\ImageProxy\Eel;

/***************************************************************
 *  (c) 2022 networkteam GmbH - all rights reserved
 ***************************************************************/

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Asset;
use Networkteam\ImageProxy\Aspects\ThumbnailAspect;

class SourceUriHelper implements ProtectedContextAwareInterface
{
    /**
     * @var ThumbnailAspect
     * @Flow\Inject
     */
    protected $thumbnailAspect;

    /**
     * @param $asset
     * @return string The source uri of the asset
     */
    public function sourceUri(Asset $asset)
    {
        return $this->thumbnailAspect->getSourceUri($asset);
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
