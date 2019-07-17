<?php

namespace Kaliba\Filesystem;
use Kaliba\Filesystem\Contracts\ResizeImageInterface;

Class ResizeImage implements ResizeImageInterface
{
    const AUTO = 0;
    const EXACT = 1;
    const PORTRAIT = 2;
    const LANDSCAPE = 3;
    const CROP = 4;
   
    /**
     *
     * @var string
     */
    private $fileName;
    
    /**
     *
     * @var string
     */
    private $image;
    
    /**
     *
     * @var int
     */
    private $imageQuality = 100;

    /**
     *
     * @var int
     */
    private $width;
    
    /**
     *
     * @var int
     */
    private $height;
    
    /**
     *
     * @var string
     */
    private $imageResized;
    
    /**
     *
     * @var string
     */
    private $savePath;
    
    /**
     * Class constructor requires to send through the image filename
     *
     * @param string $fileName - Filename of the image you want to resize
     */
    function __construct($fileName)
    {
        $this->fileName = $fileName;
        // *** Open up the file
        $this->image = $this->openImage($fileName);
         // *** Get width and height
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);
    }
    
    /**
     * Resize the image to these set dimensions
     *
     * @param  int $newWidth        	- Max width of the image
     * @param  int $newHeight       	- Max height of the image
     * @param  int $option              - Scale option for the image
     *
     * @return Save new image
     */
    public function resize($newWidth, $newHeight, $option=0)
    {
        // *** Get optimal width and height - based on $option
        $optionArray = $this->getDimensions($newWidth, $newHeight, strtolower($option));

        $optimalWidth  = $optionArray['optimalWidth'];
        $optimalHeight = $optionArray['optimalHeight'];

        // *** Resample - create image canvas of x, y size
        $this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
        imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);

        // *** if option is 'crop', then crop too
        if ($option == self::CROP) {
            $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
        }
    }
    
    /**
     * Set the image quality
     * @param int $quality   - The quality level of image to create
     */
    public function imageQuality($quality)
    {
        $this->imageQuality = $quality;
    }

    /**
     * Save the image as the image type the original image was
     *
     * @param  String $savePath     - The path to store the new image
     * @param  int $imageQuality 	  - The quality level of image to create
     *
     * @return Saves the image
     */
    public function saveImage($savePath=null, $imageQuality=null)
    {
        if(empty($savePath)){
            $savePath = $this->fileName;
        }
        if(empty($imageQuality)){
            $imageQuality = $this->imageQuality;
        }
        // *** Get extension
        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);

        switch($extension)
        {
            case '.jpg':
            case '.jpeg':
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($this->imageResized, $savePath, $imageQuality);
                }
                break;
            case '.gif':
                if (imagetypes() & IMG_GIF) {
                    imagegif($this->imageResized, $savePath);
                }
                break;
            case '.png':
                // *** Scale quality from 0-100 to 0-9
                $scaleQuality = round(($imageQuality/100) * 9);

                // *** Invert quality setting as 0 is best, not 9
                $invertScaleQuality = 9 - $scaleQuality;

                if (imagetypes() & IMG_PNG) {
                    imagepng($this->imageResized, $savePath, $invertScaleQuality);
                }
                break;

            default:
                // *** No extension - No save.
                break;
        }

        imagedestroy($this->imageResized);
        
        $this->savePath = $savePath;
        
    }
    
    /**
     * Get the absolute file path of the saved image
     * @return string
     */
    public function getSavePath()
    {
        return $this->savePath;
    }
    
    /**
     * Set the image variable by using image create
     *
     * @param string $file - The image filename
     */
    private function openImage($file)
    {
        // *** Get extension
        $extension = strtolower(strrchr($file, '.'));
        switch($extension)
        {
            case '.jpg':
            case '.jpeg':
                $img = imagecreatefromjpeg($file);
                break;
            case '.gif':
                $img = imagecreatefromgif($file);
                break;
            case '.png':
                $img = imagecreatefrompng($file);
                break;
            default:
                $img = false;
                break;
        }
        return $img;
    }
    
    /**
     * Calculate the new dimensions of the image
     *
     * @param  int $newWidth        	- Max width of the image
     * @param  int $newHeight       	- Max height of the image
     * @param  int $option              - Scale option for the image
     * @return Saves the image
     */
    private function getDimensions($newWidth, $newHeight, $option)
    {
        switch ($option)
        {
            case self::EXACT:
                $optimalWidth = $newWidth;
                $optimalHeight= $newHeight;
                break;
            case self::PORTRAIT:
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;
                break;
            case self::LANDSCAPE:
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);
                break;
            case self::AUTO:
                $optionArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
            case self::CROP:
                $optionArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
        }
        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    /**
     * Get the resized width from the height keeping the aspect ratio
     *
     * 
     * @param  int $newHeight - Max image height
     *
     * @return Width keeping aspect ratio
     */
    private function getSizeByFixedHeight($newHeight)
    {
        $ratio = $this->width / $this->height;
        $newWidth = $newHeight * $ratio;
        return $newWidth;
    }

    /**
     * Get the resized height from the width keeping the aspect ratio
     *
     * @param  int $newWidth - Max image width
     *
     * @return Height keeping aspect ratio
     */
    private function getSizeByFixedWidth($newWidth)
    {
        $ratio = $this->height / $this->width;
        $newHeight = $newWidth * $ratio;
        return $newHeight;
    }

    /**
     * Get the resized image auto computed by width and height keeping the aspect ratio
     *
     * @param  int $height - Max image height
     *
     * @return Width keeping aspect ratio
     */
    private function getSizeByAuto($newWidth, $newHeight)
    {
        
        if ($this->height < $this->width)
        {
            // *** Image to be resized is wider (landscape)
            $optimalWidth = $newWidth;
            $optimalHeight= $this->getSizeByFixedWidth($newWidth);
        }
        elseif ($this->height > $this->width)
        {
            // *** Image to be resized is taller (portrait)
            $optimalWidth = $this->getSizeByFixedHeight($newHeight);
            $optimalHeight= $newHeight;
        }
        else
        {
            // *** Image to be resized is a square
            if ($newHeight < $newWidth) {
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);
            } else if ($newHeight > $newWidth) {
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;
            } else {
                // *** Square being resized to a square
                $optimalWidth = $newWidth;
                $optimalHeight= $newHeight;
            }
        }

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    /**
     * Get the resized cropped image  keeping the aspect ratio
     *
     * @param  int $height - Max image height
     *
     * @return Width keeping aspect ratio
     */
    private function getOptimalCrop($newWidth, $newHeight)
    {

        $heightRatio = $this->height / $newHeight;
        $widthRatio  = $this->width /  $newWidth;

        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }

        $optimalHeight = $this->height / $optimalRatio;
        $optimalWidth  = $this->width  / $optimalRatio;

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }
    
    private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)
    {
        // *** Find center - this will be used for the crop
        $cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
        $cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );

        $crop = $this->imageResized;
        //imagedestroy($this->imageResized);

        // *** Now crop from center to exact requested size
        $this->imageResized = imagecreatetruecolor($newWidth , $newHeight);
        imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
    }

}