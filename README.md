# Networkteam.ImageProxy

Neos image and thumbnail serving via imgproxy, a fast and scalable microservice for image manipulation

## Installation

Install the package via Composer:

```sh
composer require networkteam/image-proxy
```

Add settings for your imgproxy instance:

```shell
cat <<EOF > Configuration/Settings.ImageProxy.yaml
Networkteam:
  ImageProxy:
    # The imgproxy base URL
    imgproxyUrl: 'http://localhost:8080'
    # An example key ('secret')
    key: '736563726574'
    # An example salt ('hello')
    salt: '68656C6C6F'
EOF
```

Leave key and salt empty if you didn't set a key and salt for imgproxy (don't do this in production).

Note: the URL should be a publicly reachable URL.

Note: When everything works for you, previously generated thumbnails can be removed with `./flow media:clearthumbnails`. They are not used anymore.

### Compatibility

See the following table for the correct plugin version to choose:

| Neos CMS      | Plugin version |
|---------------|----------------|
| >= 8.3        | 2.x            |
| >= 4.0, < 9.0 | 1.x            |


## Running imgproxy via Docker

### Serving images from S3

```shell
docker run -p 8080:8080 -it \
  -e IMGPROXY_USE_S3=true \
  -e IMGPROXY_S3_ENDPOINT=https://your-minio-or-s3-endpoint.tld \
  -e AWS_ACCESS_KEY_ID=your-access-key \
  -e AWS_SECRET_ACCESS_KEY=your-secret-key \
  -e IMGPROXY_KEY=736563726574 \
  -e IMGPROXY_SALT=68656C6C6F \
  darthsim/imgproxy
```

## How does it work?

* All calls to `AssetService->getThumbnailUriAndSizeForAsset()` are intercepted via AOP.
* If the requested asset is an image, a URL for imgproxy with respective processing instructions is generated.
* If a S3 storage is used, the aspect will automatically generate a `s3://` URL for the imgproxy source,
  otherwise the public URL to the original asset resource will be used as the source.
* Neos will **not generate any thumbnail**, they are generated ad-hoc from imgproxy as soon as a client requests it.

Note: make sure to add a caching proxy / CDN on top of imgproxy, it has no built-in caching!
