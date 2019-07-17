<?php

namespace Kaliba\Http\Helpers;

use Kaliba\Http\UploadFile;

/**
 * FileBag is a container for uploaded files.
 * This class is derived from Symphony Http Foundation
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FileBag extends ParameterBag
{
    /**
     * Constructor.
     *
     * @param array $files An array of HTTP files
     */
    public function __construct(array $files = array())
    {

        $global = UploadFile::createFromGrobal($files);
        parent::__construct($global);
    }
    
}
