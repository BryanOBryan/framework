<?php

namespace Kaliba\Filesystem\Contracts;

interface ResizeImageInterface 
{
   /**
     * Resize the image to these set dimensions
     *
     * @param  int $newWidth        	- Max width of the image
     * @param  int $newHeight       	- Max height of the image
     * @param  int $option              - Scale option for the image
     *
     * @return Save new image
     */
    public function resize($newWidth, $newHeight, $option=0);
    
    /**
     * Set the image quality
     * @param int $quality   - The quality level of image to create
     */
    public function imageQuality($quality);
    
    /**
     * Save the image as the image type the original image was
     *
     * @param  String $savePath     - The path to store the new image
     * @param  int $imageQuality 	  - The quality level of image to create
     *
     * @return Saves the image
     */
    public function saveImage($savePath, $imageQuality=100);
    
    /**
     * Get the absolute file path of the saved image
     * @return string
     */
    public function getSavePath();
     
}
