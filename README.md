# image
A simple class for image resizing and cropping.
Used only GD library.

Installation
------------
```code
	composer require gozoro/image
```

Usage
-----
```php
$file = "image.jpg";
$image = \gozoro\image\Image($file);
$image->resize(400, 400)->crop(200, 200)->save(); // save to image.jpg
$image->resize(400, 400)->crop(200, 200)->saveAs("image2.jpg");
```

**Using resize (portrait)**
```php
$file = "image400x600.jpg"; //width:400px, height:600px
$image = \gozoro\image\Image($file);
$image->resize(200)->save(); //result image: 200x300

$image = \gozoro\image\Image($file);
$image->resize(null, 200)->save(); //result image: 133x200

$image = \gozoro\image\Image($file);
$image->resize(200, 200)->save(); //result image: 133x200
```

**Using resize (landscape)**
```php
$file = "image600x400.jpg"; //width:600px, height:400px
$image = \gozoro\image\Image($file);
$image->resize(200)->save(); //result image: 200x133

$image = \gozoro\image\Image($file);
$image->resize(null, 200)->save(); //result image: 300x200

$image = \gozoro\image\Image($file);
$image->resize(200, 200)->save(); //result image: 200x133
```

**Using copping**
```php
$file = "image600x400.jpg"; //width:600px, height:400px
$image = \gozoro\image\Image($file);
$image->crop(200, 200, $x=0, $y=0)->saveAs("image200x200.jpg");
$image->cropLeft(200)->saveAs("image_left_200.jpg"); // crop(200, auto, 0, 0)
$image->cropRight(200)->saveAs("image_right_200.jpg"); // crop(200, auto, 600-200, 0)
$image->cropCenter(200)->saveAs("image_center_200.jpg"); // crop(200, auto, 200, 400)
$image->cropTop(200)->saveAs("image_top_200.jpg"); // crop(auto, 200, 0, 0)
$image->cropBottom(200)->saveAs("image_bottom_200.jpg"); // crop(auto, 200, 0, 400-200)
$image->cropSquare()->saveAs("image_square.jpg"); // crop(400, 400, 100, 0)
```