<?php

namespace Networkteam\ImageProxy\Tests\Unit;

use GuzzleHttp\Psr7\Uri;
use Networkteam\ImageProxy\ImgproxyBuilder;
use Networkteam\ImageProxy\Model\Dimension;

class ImgproxyBuilderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @test
     */
    public function generateLocalUrlWithoutSignature()
    {
        $builder = new ImgproxyBuilder("http://localhost:8084");
        $url = $builder->buildUrl("local:///path/to/image.jpg")
            ->resize(ImgproxyBuilder::RESIZE_TYPE_FIT, 300, 200, false, true)
            ->extension('png')
            ->build();

        $this->assertEquals('http://localhost:8084/insecure/rs:fit:300:200:0:1/bG9jYWw6Ly8vcGF0aC90by9pbWFnZS5qcGc.png', $url);
    }

    /**
     * @test
     */
    public function generateLocalUrlWithSignature()
    {
        $builder = new ImgproxyBuilder("http://localhost:8084", '736563726574', '68656C6C6F');
        $url = $builder->buildUrl("local:///path/to/image.jpg")
            ->resize(ImgproxyBuilder::RESIZE_TYPE_FILL, 300, 400, false, false)
            ->extension('png')
            ->build();

        $this->assertEquals('http://localhost:8084/4EjfKMTf6eZ9q6_n5l3Woc3AsbRfsXJ6lgNbqe2mOvY/rs:fill:300:400:0:0/bG9jYWw6Ly8vcGF0aC90by9pbWFnZS5qcGc.png', $url);
    }

    /**
     * @test
     */
    public function generateLocalUrlWithFileName()
    {
        $builder = new ImgproxyBuilder("http://localhost:8084");
        $url = $builder->buildUrl("local:///path/to/image.jpg")
            ->extension('png')
            ->fileName("test-image")
            ->build();

        $this->assertEquals('http://localhost:8084/insecure/fn:test-image/bG9jYWw6Ly8vcGF0aC90by9pbWFnZS5qcGc.png', $url);
    }

    /**
     * @test
     */
    public function generateLocalUrlWithNumericFilenameCanBeParsedAsUri()
    {
        $builder = new ImgproxyBuilder("/_img");
        $url = $builder->buildUrl("local:///path/to/1.jpg")
            ->fileName('1')
            ->resize(ImgproxyBuilder::RESIZE_TYPE_FIT, 300, 200, false, true)
            ->build();

        $uri = new Uri($url);

        $this->assertEquals('/_img/insecure/fn:_1/rs:fit:300:200:0:1/bG9jYWw6Ly8vcGF0aC90by8xLmpwZw', $url, "Generated Url can be pased");
    }

    public function resizeExamples(): array
    {
        return [
            [
                ImgproxyBuilder::RESIZE_TYPE_FILL, 300, 400, false, false,
                'http://localhost:8084/insecure/rs:fill:300:400:0:0/bG9jYWw6Ly8vcGF0aC90by9pbWFnZS5qcGc.png'
            ],
            [
                ImgproxyBuilder::RESIZE_TYPE_FILL, 300, null, false, false,
                'http://localhost:8084/insecure/rs:fill:300::0:0/bG9jYWw6Ly8vcGF0aC90by9pbWFnZS5qcGc.png'
            ],
            [
                ImgproxyBuilder::RESIZE_TYPE_FILL, 300, 0, false, false,
                'http://localhost:8084/insecure/rs:fill:300::0:0/bG9jYWw6Ly8vcGF0aC90by9pbWFnZS5qcGc.png'
            ],
            [
                ImgproxyBuilder::RESIZE_TYPE_FILL, null, 400, false, false,
                'http://localhost:8084/insecure/rs:fill::400:0:0/bG9jYWw6Ly8vcGF0aC90by9pbWFnZS5qcGc.png'
            ],
            [
                ImgproxyBuilder::RESIZE_TYPE_FILL, 0, 400, false, false,
                'http://localhost:8084/insecure/rs:fill::400:0:0/bG9jYWw6Ly8vcGF0aC90by9pbWFnZS5qcGc.png'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider resizeExamples
     */
    public function buildUrlWithResize(?string $resizingType, ?int $width, ?int $height, ?bool $enlarge, ?bool $extend, string $expectedUrl)
    {
        $builder = new ImgproxyBuilder("http://localhost:8084");
        $url = $builder->buildUrl("local:///path/to/image.jpg")
            ->resize($resizingType, $width, $height, $enlarge, $extend)
            ->extension('png')
            ->build();

        $this->assertEquals($expectedUrl, $url, "Build with $resizingType");
    }

    /**
     * @test
     * @dataProvider expectedSizeExamples
     */
    public function expectedSize(Dimension $actualDimension, Dimension $targetDimension, string $resizingType, bool $enlarge, Dimension $expectedDimension)
    {
        $actualExpectedDimension = ImgproxyBuilder::expectedSize($actualDimension, $targetDimension, $resizingType, $enlarge);
        $shouldEnlarge = $enlarge ? "true" : "false";
        $this->assertEquals($expectedDimension, $actualExpectedDimension, "actual: $actualDimension | target: $targetDimension | $resizingType | ${shouldEnlarge}");
    }

    public function expectedSizeExamples(): array
    {
        return [
            [
                new Dimension(1000, 800),
                new Dimension(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(375, 300),
            ],
            [
                new Dimension(400, 300),
                new Dimension(1000, 800),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                true,
                new Dimension(1000, 750),
            ],
            [
                new Dimension(400, 300),
                new Dimension(1000, 800),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(1000, 500),
                new Dimension(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(400, 200),
            ],
            [
                new Dimension(1000, 800),
                new Dimension(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FILL,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(400, 300),
                new Dimension(1000, 800),
                ImgproxyBuilder::RESIZE_TYPE_FILL,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(400, 300),
                new Dimension(1000, 800),
                ImgproxyBuilder::RESIZE_TYPE_FILL,
                true,
                new Dimension(1000, 800),
            ],
            [
                new Dimension(1000, 500),
                new Dimension(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FILL,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(800, 600),
                new Dimension(200, 300),
                ImgproxyBuilder::RESIZE_TYPE_FORCE,
                false,
                new Dimension(200, 300),
            ],
            [
                new Dimension(800, 600),
                new Dimension(0, 0),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(800, 600),
            ],
            [
                new Dimension(800, 600),
                new Dimension(400, 0),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(800, 600),
                new Dimension(0, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(0, 0),
                new Dimension(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(400, 300),
                new Dimension(800, 600),
                ImgproxyBuilder::RESIZE_TYPE_FORCE,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(null, null),
                new Dimension(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(400, 300),
            ],
            [
                new Dimension(0, 0),
                new Dimension(0, 0),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(0, 0),
            ],
            [
                new Dimension(null, null),
                new Dimension(0, 0),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimension(0, 0),
            ],
            [
                new Dimension(400, 300),
                new Dimension(800, 600),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                true,
                new Dimension(800, 600)
            ],
        ];
    }
}
