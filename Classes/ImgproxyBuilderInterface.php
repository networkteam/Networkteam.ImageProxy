<?php

namespace Networkteam\ImageProxy;

use Networkteam\ImageProxy\Model\Dimensions;

interface ImgproxyBuilderInterface
{
    public static function expectedSize(
        Dimensions $actualDimension,
        Dimensions $targetDimension,
        string $resizingType,
        bool $enlarge
    ): Dimensions;

    public function buildUrl(string $sourceUrl): ImgproxyUrl;

    public function generateUrl(ImgproxyUrl $imgproxyUrl): string;
}