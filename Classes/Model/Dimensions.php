<?php

namespace Networkteam\ImageProxy\Model;

class Dimensions
{
    protected int $width;
    protected int $height;

    public function __construct(?int $width, ?int $height)
    {
        $this->width = $width ?? 0;
        $this->height = $height ?? 0;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getAspectRatio(): float
    {
        if ($this->height > 0) {
            return $this->width / $this->height;
        }

        return 0;
    }

    public function isZero(): bool
    {
        return $this->noHeight() && $this->noWidth();
    }

    public function noWidth(): bool
    {
        return $this->width === 0;
    }

    public function noHeight(): bool
    {
        return $this->height === 0;
    }

    /**
     * Check if both width and height are greater than or equal to the given dimensions,
     * so the other dimensions fits into this.
     *
     * @param Dimensions $otherDimension
     * @return bool
     */
    public function contains(Dimensions $otherDimension): bool
    {
        return $this->width >= $otherDimension->getWidth() && $this->height >= $otherDimension->getHeight();
    }

    function __toString()
    {
        return "{$this->getWidth()}:{$this->getHeight()}={$this->getAspectRatio()}";
    }
}
