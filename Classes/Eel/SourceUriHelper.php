<?php

namespace Networkteam\ImageProxy\Eel;

/***************************************************************
 *  (c) 2022 networkteam GmbH - all rights reserved
 ***************************************************************/

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Networkteam\ImageProxy\Aspects\ThumbnailAspect;

class SourceUriHelper implements ProtectedContextAwareInterface
{

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * Get the source URI for fetching a resource.
     *
     * @param PersistentResource $resource The resource to generate the URI for
     * @return string The source URI of the resource (could be either a http or s3 URL depending on the resource storage)
     */
    public function sourceUri(PersistentResource $resource): string
    {
        $sourceUri = '';

        $resourceCollection = $this->resourceManager->getCollection($resource->getCollectionName());
        $resourceStorage = $resourceCollection->getStorage();
        if (get_class($resourceStorage) === 'Flownative\Aws\S3\S3Storage') {
            $bucketName = $resourceStorage->getBucketName();
            $keyPrefix = $resourceStorage->getKeyPrefix();
            $sourceUri = sprintf('s3://%s/%s/%s', $bucketName, rtrim($keyPrefix, '/'), $resource->getSha1());
        } else {
            $sourceUri = $this->resourceManager->getPublicPersistentResourceUri($resource);
        }

        return $sourceUri;
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
