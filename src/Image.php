<?php

namespace gozoro\image;




/**
 * A simple class for image resizing, cropping.
 * Used GD library.
 *
 * @author gozoro <gozoro@yandex.ru>
 */
class Image
{
	/**
	 * Stores file name.
	 * @var string
	 */
	protected $filename;

	/**
	 * Stores extension of file.
	 * @var string
	 */
	private $ext;

	/**
	 * Image of GD
	 * @var resource
	 */
	protected $image;



	/**
	 * A simple class for image resizing, cropping.
	 * @param string $filename image file with extensions: jpeg, jpg, png, gif
	 */
	public function __construct($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * Creating image resource
	 * @return resource
	 * @throws ImageException
	 */
	protected function createImage()
	{
		$filename = $this->filename;
		$ext = self::parseExtension($filename);

		switch($ext)
		{
			case 'jpg':
			case 'jpeg':
				return imagecreatefromjpeg($filename);
			case 'png':
				return imagecreatefrompng($filename);
			case 'gif':
				return imagecreatefromgif($filename);
			default:
				return $this->createDefaultImage();
		}
	}

	/**
	 * Creates default image.
	 * Now throw ImageException "nknow image format".
	 * You can override this method for your behavior.
	 */
	protected function createDefaultImage()
	{
		$filename = $this->filename;
		$ext = self::parseExtension($filename);
		$this->throwException("Unknow image format - $ext.");
	}


	/**
	 * Returns image resource for use other function of library GD.
	 * @return resource
	 */
	public function image()
	{
		if(!isset($this->image))
			$this->image = $this->createImage();

		return $this->image;
	}

	/**
	 * Returns full path to image file.
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * Returns base name of image file.
	 * @return string
	 */
	public function getBaseName()
	{
		return basename($this->getFilename());
	}

	/**
	 * Sets image file extension.
	 * @param string $ext jpg|png|gif
	 * @return static
	 */
	public function setExtension($ext)
	{
		$this->ext = strtolower($ext);
		return $this;
	}


	/**
	 * Returns extension of image file. Used strtolower().
	 * @return string|null
	 */
	public function getExtension()
	{
		if(!isset($this->ext))
		{
			$this->ext = self::parseExtension($this->filename);
		}

		return $this->ext;
	}

	/**
	 * Returns width of image file.
	 * @return int
	 */
	public function getWidth()
	{
		return imagesx($this->image());
	}

	/**
	 * Returns height of image file.
	 * @return int
	 */
	public function getHeight()
	{
		return imagesy($this->image());
	}

	/**
	 * Returns TRUE if image is landscape (width > height).
	 * @return bool
	 */
	public function isLandscape()
	{
		return ($this->getWidth() > $this->getHeight());
	}

	/**
	 * Returns TRUE if image is portrait (height > width).
	 * @return bool
	 */
	public function isPortrait()
	{
		return ($this->getHeight() > $this->getWidth());
	}

	/**
	 * Returns TRUE if image is square (width == height).
	 * @return bool
	 */
	public function isSquare()
	{
		return ($this->getWidth() == $this->getHeight());
	}

	/**
	 * Resizing image.
	 *
	 * @param int $maxWidth max width to resizing (px)
	 * @param int $maxHeight  max height to resizing (px)
	 * @param bool $keepAspectRatio if TRUE, preserves the aspect ratio of the image,
	 *                              otherwise allows you to distort the image to achieve dimensions.
	 * @param bool $allowUpscaling if TRUE, allows to increase the image size if necessary
	 *                             over the original size, otherwise will return the original image
	 *                             (with original dimensions).
	 * @return static
	 */
	public function resize($maxWidth=null, $maxHeight=null, $keepAspectRatio=true, $allowUpscaling=false)
	{
		if(is_null($maxWidth) and is_null($maxHeight))
		{
			// return as is
			return $this;
		}

		$widthSrc  = $this->getWidth();
		$heightSrc = $this->getHeight();
		$factor    = $heightSrc / $widthSrc;


		if(!is_null($maxWidth) and is_null($maxHeight))
		{
			$widthDst  = (int)$maxWidth;
			$heightDst = round($widthDst * $factor);
		}
		elseif(is_null($maxWidth) and !is_null($maxHeight))
		{
			$heightDst = (int)$maxHeight;
			$widthDst  = round($heightDst / $factor);
		}
		else
		{
			if($keepAspectRatio)
			{
				if($widthSrc >= $heightSrc)
				{
					// landscape
					$widthDst  = (int)$maxWidth;
					$heightDst = round($widthDst * $factor);
				}
				else
				{
					// portrait
					$heightDst = (int)$maxHeight;
					$widthDst  = round($heightDst / $factor);
				}
			}
			else
			{
				$widthDst = (int)$maxWidth;
				$heightDst = (int)$maxHeight;
			}
		}

		if(!$allowUpscaling)
		{
			if($widthDst > $widthSrc or $heightDst > $heightSrc)
			{
				return $this;
			}
		}

		$imageSrc = $this->image();
		$imageDst = imageCreateTrueColor($widthDst, $heightDst);

		if(imageCopyResampled($imageDst, $imageSrc, 0,0,0,0, $widthDst, $heightDst, $widthSrc, $heightSrc))
		{
			imagedestroy($imageSrc);
			$this->image = $imageDst;
			return $this;
		}
		else
			$this->throwException("Resize is failed.");
	}

	/**
	 * Cropping image.
	 *
	 * @param int $width cropping width
	 * @param int $height cropping height
	 * @param int $src_x X coordinate of the top left corner of the cropping (default 0)
	 * @param int $src_y Y coordinate of the top left corner of the cropping (default 0)
	 * @return static
	 */
	public function crop($width, $height, $src_x=0, $src_y=0 )
	{
		$widthSrc = $this->getWidth();
		$heightSrc = $this->getHeight();

		if($width > ($widthSrc-$src_x))
			$width = $widthSrc-$src_x;

		if($height > ($heightSrc-$src_y))
			$height = $heightSrc-$src_y;

		$imageSrc = $this->image();
		$imageDst = imageCreateTrueColor($width, $height);

		if(imagecopy($imageDst, $imageSrc, 0, 0, $src_x, $src_y, $widthSrc, $heightSrc))
		{
			imagedestroy($imageSrc);
			$this->image = $imageDst;
			return $this;
		}
		else
			$this->throwException("Crop is failed.");
	}

	/**
	 * Cropping left to a given width.
	 * @param int $width cropping width (px)
	 * @return static
	 */
	public function cropLeft($width)
	{
		$height = $this->getHeight();
		return $this->crop($width, $height);
	}

	/**
	 * Cropping right to a given width.
	 * @param int $width cropping width (px)
	 * @return static
	 */
	public function cropRight($width)
	{
		$height = $this->getHeight();
		return $this->crop($width, $height, $this->getWidth()-$width);
	}

	/**
	 * Cropping center to a given size.
	 * @param int $size cropping width or height (px)
	 * @return type
	 */
	public function cropCenter($size)
	{
		if($this->isLandscape())
		{
			$width = $size;
			$height = $this->getHeight();
			return $this->crop($width, $height, ($this->getWidth()-$width)/2);
		}
		elseif($this->isPortrait())
		{
			$height = $size;
			$width = $this->getWidth();
			return $this->crop($width, $height, 0, ($this->getHeight()-$height)/2);
		}
		else
		{
			$d = ($this->getWidth() - $size)/2;
			return $this->crop($size+$d, $size+$d, $d/2, $d/2)->resize($size, $size);
		}
	}

	/**
	 * Cropping top to a given height.
	 * @param int $height cropping height (px)
	 * @return static
	 */
	public function cropTop($height)
	{
		$width = $this->getWidth();
		return $this->crop($width, $height);
	}

	/**
	 * Cropping bottom to a given height.
	 * @param int $height cropping height (px)
	 * @return static
	 */
	public function cropBottom($height)
	{
		$width = $this->getWidth();
		return $this->crop($width, $height, 0, $this->getHeight()-$height);
	}

	/**
	 * Cropping image to square.
	 * @return static
	 */
	public function cropSquare()
	{
		if($this->isLandscape())
		{
			$height = $this->getHeight();
			$width = $height;
			return $this->crop($width, $height, ($this->getWidth()-$width)/2);
		}
		elseif($this->isPortrait())
		{
			$width = $this->getWidth();
			$height = $width;
			return $this->crop($width, $height, 0, ($this->getHeight()-$height)/2);
		}
		else
		{
			return $this;
		}
	}

	/**
	 * Creating image file.
	 * @param string $filename
	 * @return bool
	 * @throws ImageException
	 */
	private function createImageFile($filename)
	{
		$ext = $this->getExtension();
		$img = $this->image();

		switch($ext)
		{
			case 'jpg':
			case 'jpeg': return imagejpeg($img, $filename);
			case 'png': imagesavealpha($img, true); return imagepng($img, $filename);
			case 'gif': return imagegif($img, $filename);
			default: $this->throwException("Unknow image format - $ext.");
		}
		return true;
	}

	/**
	 * Returns TRUE if image file extsts.
	 * @return bool
	 */
	public function isExists()
	{
		return file_exists($this->getFilename());
	}

	/**
	 * Runs before save image. Returns TRUE.
	 * @return boolean
	 */
	public function beforeSave()
	{
		return true;
	}

	/**
	 * Runs after save image. Returns TRUE.
	 * @return boolean
	 */
	public function afterSave()
	{
		return true;
	}

	/**
	 * Saving image to $filename.
	 * @param string $filename full path to destination image file with extensions jpeg, jpg, png, gif.
	 * @return bool Returns TRUE if saving is success.
	 * @throws ImageException
	 */
	public function saveAs($filename)
	{
		if($this->beforeSave())
		{
			$path = dirname($filename);

			if(!file_exists($path))
			{
				$this->throwException("Directory $path is not exists.");
			}

			if(is_writable($path))
			{
				if($this->createImageFile($filename))
				{
					$this->afterSave();
					return true;
				}
				else
				{
					$this->throwException("Image $filename could not be saved.");
				}
			}
			else
			{
				$this->throwException("Directory $path is not writable.");
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Saves the image to a path that returns method getFilename().
	 * @return bool Returns TRUE if saving is success.
	 */
	public function save()
	{
		return $this->saveAs($this->getFilename());
	}

	/**
	 * Throws image exception
	 * @param string $message
	 * @throws ImageException
	 */
	protected function throwException($message)
	{
		throw new ImageException($message);
	}

	/**
	 * Returns file extension.
	 * @param string $filename
	 * @return string
	 */
	public static function parseExtension($filename)
	{
		$name = basename($filename);
		$parts = explode('.', $name);

		if(count($parts)>1)
			return strtolower( $parts[ count($parts)-1 ] );
		else
			return '';
	}
}


/**
 * Image exception.
 */
class ImageException extends \Exception{}
