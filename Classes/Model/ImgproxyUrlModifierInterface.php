<?php

namespace Networkteam\ImageProxy\Model;

use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Networkteam\ImageProxy\ImgproxyUrl;

interface ImgproxyUrlModifierInterface
{
    public function modify(ImgproxyUrl $url, ThumbnailConfiguration $configuration): void;
}