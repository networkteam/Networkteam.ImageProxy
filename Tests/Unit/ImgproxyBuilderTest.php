<?php

namespace Networkteam\ImageProxy\Tests\Unit;

use GuzzleHttp\Psr7\Uri;
use Networkteam\ImageProxy\ImgproxyBuilder;
use Networkteam\ImageProxy\Model\Dimensions;

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

        $this->assertEquals('/_img/insecure/fn:_1/rs:fit:300:200:0:1/bG9jYWw6Ly8vcGF0aC90by8xLmpwZw', $url);
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

        $this->assertEquals($expectedUrl, $url);
    }

    /**
     * @test
     * @dataProvider expectedSizeExamples
     */
    public function expectedSize(Dimensions $actualDimension, Dimensions $targetDimension, string $resizingType, bool $enlarge, Dimensions $expectedDimension)
    {
        $actualExpectedDimension = ImgproxyBuilder::expectedSize($actualDimension, $targetDimension, $resizingType, $enlarge);
        $shouldEnlarge = $enlarge ? "true" : "false";
        $this->assertEquals($expectedDimension, $actualExpectedDimension, "actual: $actualDimension | target: $targetDimension | $resizingType | ${shouldEnlarge}");
    }

    public function expectedSizeExamples(): array
    {
        return [
            [
                new Dimensions(1000, 800),
                new Dimensions(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(375, 300),
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(1000, 800),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                true,
                new Dimensions(1000, 750),
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(1000, 800),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(1000, 500),
                new Dimensions(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 200),
            ],
            [
                new Dimensions(1000, 800),
                new Dimensions(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FILL,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(1000, 800),
                ImgproxyBuilder::RESIZE_TYPE_FILL,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(1000, 800),
                ImgproxyBuilder::RESIZE_TYPE_FILL,
                true,
                new Dimensions(1000, 800),
            ],
            [
                new Dimensions(1000, 500),
                new Dimensions(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FILL,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(800, 600),
                new Dimensions(200, 300),
                ImgproxyBuilder::RESIZE_TYPE_FORCE,
                false,
                new Dimensions(200, 300),
            ],
            [
                new Dimensions(800, 600),
                new Dimensions(0, 0),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(800, 600),
            ],
            [
                new Dimensions(800, 600),
                new Dimensions(400, 0),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(800, 600),
                new Dimensions(0, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(0, 0),
                new Dimensions(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(800, 600),
                ImgproxyBuilder::RESIZE_TYPE_FORCE,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(null, null),
                new Dimensions(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300),
            ],
            [
                new Dimensions(0, 0),
                new Dimensions(0, 0),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(0, 0),
            ],
            [
                new Dimensions(null, null),
                new Dimensions(0, 0),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(0, 0),
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(800, 600),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                true,
                new Dimensions(800, 600)
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(400, 600),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                true,
                new Dimensions(400, 300)
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(1000, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300)
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(400, 1000),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300)
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(200, 1000),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(200, 150)
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(1000, 1000),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300)
            ],
            [
                new Dimensions(400, 300),
                new Dimensions(400, 300),
                ImgproxyBuilder::RESIZE_TYPE_FIT,
                false,
                new Dimensions(400, 300)
            ],
        ];
    }
}
