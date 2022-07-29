<?php

namespace Networkteam\ImageProxy\Model;

class Dimension
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

    public function isGreater(Dimension $otherDimension) : bool
    {
        return $this->height * $this->width > $otherDimension->getHeight() * $otherDimension->getWidth();
    }

    function __toString()
    {
        return "{$this->getWidth()}:{$this->getHeight()}={$this->getAspectRatio()}";
    }
}
