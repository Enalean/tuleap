# Project background images

This section aims to provide some help to developers willing to propose
additional project background images.

TL;DR: Take an existing image like `beach-daytime`, search occurrences
of it in the code and do the same.

## Image format & size

Image is provided in two sizes:

-   `1680×850` for smaller resolutions
-   `2400×850` for bigger resolutions

If your original image is a photo, there is a high chance that it has
been save in JPEG format. However, in order to save bandwidth we should
convert it to WebP instead.

It can be done with the following command, assuming that the image is
already in the `2400×850` resolution:

``` bash
$> cwebp -preset photo -mt -m 6 -pass 10 -af my-image.jpg -o my-image-2400px.webp
```

Or, if you have a bunch of images:

``` bash
$> find . -name '*.jpg' -exec bash -c \
    'cwebp -preset photo -mt -m 6 -pass 10 -af \
        $1 -o ${1%.jpg}-2400px.webp' _ {} \;
```

Now we can crop the original image down to a `1680×850` pixels:

``` bash
$> cwebp -preset photo -mt -m 6 -pass 10 -af \
  -crop 360 0 1680 850 \
  my-image.jpg -o my-image-1680px.webp
```

Or if you have a bunch of images:

``` bash
$> find . -name '*.jpg' -exec bash -c \
    'cwebp -preset photo -mt -m 6 -pass 10 -af \
        -crop 360 0 1680 850 \
        $1 -o ${1%.jpg}-1680px.webp' -- {} \;
```

Note: the image is automatically cropped to center the image. You may
need to adjust the x position of the `-crop` parameter depending on the
background image --- for example to favor dark/blurry zone over vibrant
colors.

## Image dominant color

Even if the background image is relatively small, it can take some time
to load on the browser. In order to limit the flickering and offer a
smooth transition, a solid color is displayed. T his color should be the
dominant color of the image. You can use the following command to
extract such information:

``` bash
$> convert my-image-1680px.webp -scale 1x1\! -format '%[pixel:u]' info:-
```

For example it will output the following information:

    Decoded /tmp/magick-2917Aek1hh7mfh3h. Dimensions: 1680 x 850 . Format: lossy. Now saving...
    Saved file /tmp/magick-2917YRYRx5cxgPOm
    srgba(76,52,34,1)%

We can then take the dominant color from the last line:
`rgb(76, 52, 34)`.

The color is expected to have enough contrast when used with the same
color with 7% opacity on a white background. The color needs to be
adjusted manually to have a [contrast ratio of at least
4.5:1](https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html).

## Image definition in SCSS

Now that you have two `.webp` versions of an image, and its dominant
color, you can insert the definition in
`src/themes/common/css/project-background/_background.scss` alongside
the existing ones. Be nice, don\'t forget the attributions!

You will need to write a new scss file (it will expose the CSS custom
properties for the image) and declare it in the `src/webpack.common.js`.
The file name should be the same than the choosen key in the background
definition file. It will be referenced later as the *identifier* of the
background.

``` scss
// _background.scss
$definitions: (
    // […],
    my-image:
    (
        // Photo by John Doe
        // https://example.com/john.doe-photography/
        image-2400px: url(#{$project-background-images-path}/my-image-2400px.webp) no-repeat,
        image-1680px: url(#{$project-background-images-path}/my-image-1680px.webp) no-repeat,
        color: rgb(76, 52, 34),
        size: 100%
    ),
    // […]
);
```

``` scss
// my-image.scss
@use 'background';

.project-with-background {
    @include background.css-custom-properties('my-image');
}
```

``` js
// webpack.common.js
const project_background_themes = [
    // […],
    "my-image",
    // […],
];
```

## Image definition in PHP

Identifier of the background should be declared in the following
locations:

-   In the `HeaderBackgroundRepresentation::identifier` annotation, so
    that we expose the expected values in the OpenAPI representation.
-   In the `ProjectBackgroundSelection::ALLOWED` constant, so that we
    can propose the new image in project administration.
