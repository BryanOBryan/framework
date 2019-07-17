<?php
namespace Kaliba\Filesystem\Contracts;

/**
 * Guesses the file extension corresponding to a given mime type.
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 */
interface ExtensionGuesserInterface
{
    /**
     * Makes a best guess for a file extension, given a mime type.
     *
     * @param string $mimeType The mime type
     *
     * @return string The guessed extension or NULL, if none could be guessed
     */
    public function guess($mimeType);
}
