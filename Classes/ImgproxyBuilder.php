<?php

namespace Networkteam\ImageProxy;

use Networkteam\ImageProxy\Model\Dimension;

class ImgproxyBuilder
{

    /**
     * Resizes the image while keeping aspect ratio to fit a given size.
     */
    public const RESIZE_TYPE_FIT = 'fit';
    /**
     * Resizes the image while keeping aspect ratio to fill a given size and crops projecting parts.
     */
    public const RESIZE_TYPE_FILL = 'fill';
    /**
     * Resizes the image without keeping the aspect ratio.
     */
    public const RESIZE_TYPE_FORCE = 'force';

    private string $imgproxyUrl;
    private ?string $key = null;
    private ?string $salt = null;

    /**
     * @param string $imgproxyUrl The URL where imgproxy is publicly available
     * @param string|null $key
     * @param string|null $salt
     */
    public function __construct(string $imgproxyUrl, string $key = null, string $salt = null)
    {
        if ((string)$key !== '' && (string)$salt !== '') {
            $this->key = pack("H*", $key);
            $this->salt = pack("H*", $salt);
        }

        $this->imgproxyUrl = $imgproxyUrl;
    }

    /**
     * @param int|null $actualWidth
     * @param int|null $actualHeight
     * @param int $targetWidth
     * @param int $targetHeight
     * @param string $resizingType
     * @param bool $enlarge
     * @return Dimension
     * @internal
     * Get the expected size of the resulting image
     */
    public static function expectedSize(Dimension $actualDimension, Dimension $targetDimension, string $resizingType, bool $enlarge): Dimension
    {
        if ($actualDimension->noWidth() || $actualDimension->noHeight()) {
            return $targetDimension;
        }

        if ($targetDimension->isZero()) {
            return $actualDimension;
        }

        // Use the actual aspect ratio as the default target aspect ratio
        $targetAspectRatio = $actualDimension->getAspectRatio();

        if (!$targetDimension->noHeight() && !$targetDimension->noWidth()) {
            $targetAspectRatio = $targetDimension->getAspectRatio();
        }

        if (!$enlarge && ($targetDimension->isGreater($actualDimension))) {
            return $actualDimension;
        }

        if ($resizingType === ImgproxyBuilder::RESIZE_TYPE_FORCE || $resizingType === ImgproxyBuilder::RESIZE_TYPE_FILL) {
            return $targetDimension;
        }

        // The actual image is wider than the expected target image or target height is not known -> restrict by width
        if ($targetDimension->getHeight() === 0 || $actualDimension->getAspectRatio() > $targetAspectRatio) {
            return new Dimension($targetDimension->getWidth(), $targetDimension->getWidth() / $actualDimension->getAspectRatio());
        }

        // The actual image is narrower than the expected target image (or equal, but doesn't matter) or target width is not known -> restrict by height
        return new Dimension($actualDimension->getAspectRatio() * $targetDimension->getHeight(), $targetDimension->getHeight());
    }

    /**
     * Build an imgproxy URL by starting with a source URL to the image. Additional processing options can be set by
     * using the returned builder instance. With `$url->build()` the final URL can be generated.
     *
     * Depending on the imgproxy configuration, that could be a http, local or s3 URL.
     *
     * @param string $sourceUrl
     * @return ImgproxyUrl
     */
    public function buildUrl(string $sourceUrl): ImgproxyUrl
    {
        return new ImgproxyUrl($this, $sourceUrl);
    }

    /**
     * @param string $sourceUrl
     * @param string[] $processingOptions
     * @param string|null $extension
     * @return string
     * @internal
     * Generate an imgproxy URL with processing options and signature (if key and salt are set).
     *
     */
    public function generateUrl(string $sourceUrl, array $processingOptions, ?string $extension): string
    {
        $encodedSourceUrl = self::base64UrlEncode($sourceUrl);
        $pathAfterSignature = '/' . join('/', $processingOptions) . '/' . $encodedSourceUrl;
        if ($extension !== null) {
            $pathAfterSignature .= '.' . $extension;
        }

        if ($this->key !== null) {
            $data = $this->salt . $pathAfterSignature;
            $signature = hash_hmac('sha256', $data, $this->key, true);

            return $this->imgproxyUrl . '/' . self::base64UrlEncode($signature) . $pathAfterSignature;
        } else {
            return $this->imgproxyUrl . '/insecure' . $pathAfterSignature;
        }
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
