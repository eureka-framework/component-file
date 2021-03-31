<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\File\File;

use Eureka\Component\File\Directory\DirectoryIterator;

/**
 * Filter iterator on file extensions.
 *
 * @author Romain Cottard
 */
class FileExtensionFilterIterator extends \FilterIterator
{
    /** @var string[] */
    private array $extensions;

    /**
     * FileExtensionFilterIterator constructor.
     *
     * @param DirectoryIterator $iterator
     * @param string[]          $extensions Files extensions allowed (without dot). ['xml' => true, 'txt' => true ...]
     */
    public function __construct(DirectoryIterator $iterator, array $extensions = [])
    {
        parent::__construct($iterator);

        $this->extensions = $extensions;
    }

    /**
     * @return boolean
     */
    public function accept(): bool
    {
        return isset($this->extensions[$this->current()->getExtension()]);
    }
}
