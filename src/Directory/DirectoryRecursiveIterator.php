<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\File\Directory;

use Eureka\Component\File\File\FileInterface;
use Eureka\Component\File\File\File;
use Eureka\Component\File\File\FileInfo;

/**
 * Class to manipulate directories. Extends FileInfo (and SplFile*)
 *
 * @author Romain Cottard
 */
class DirectoryRecursiveIterator extends \RecursiveDirectoryIterator implements FileInterface
{
    /**
     * Overridden parent method. Force return type of object to instance of class 'file'.
     *
     * @param string $mode The mode for opening the file. See the fopen() documentation for descriptions of
     *        possible modes. The default is read only.
     * @param bool $useIncludePath When set to true, the filename is also searched for within the include_path
     * @param resource $context Refer to the context section of the manual for a description of contexts.
     * @return File|\SplFileInfo
     */
    public function openFile($mode = 'r', $useIncludePath = false, $context = null): \SplFileObject
    {
        $this->setFileClass(File::class);

        return parent::openFile($mode, $useIncludePath, $context);
    }

    /**
     * Overridden parent method. Force return type of object to instance of class 'FileInfo', or specified type passed
     * in argument.
     *
     * @param string|null $class Name of an SplFileInfo derived class to use.
     * @return FileInfo|\SplFileInfo
     */
    public function getPathInfo(?string $class = null): \SplFileInfo
    {
        $this->setInfoClass($class ?? FileInfo::class);

        return parent::getPathInfo();
    }

    /**
     * Overridden parent method. Force return type of object to instance of class 'FileInfo', or specified type passed
     * in argument.
     *
     * @param string $class Name of an SplFileInfo derived class to use.
     * @return FileInfo|\SplFileInfo
     */
    public function getFileInfo($class = null): \SplFileInfo
    {
        $this->setInfoClass($class ?? FileInfo::class);

        return parent::getFileInfo();
    }

    /**
     * Overridden parent method. Return current file_dir iterator
     *
     * @return $this
     */
    public function current()
    {
        $flags = $this->getFlags();

        switch (true) {
            case ($flags & self::CURRENT_AS_SELF) === self::CURRENT_AS_SELF:
                $current = $this;
                break;
            case ($flags & self::CURRENT_AS_FILEINFO) === self::CURRENT_AS_FILEINFO:
                $current = $this->getFileInfo();
                break;
            default:
                $current = parent::current();
        }

        return $current;
    }

    /**
     * Get list of directories / files recursively.
     *
     * @param  string $order
     * @return FileInfo[]
     */
    public function getList($order = 'asc'): array
    {
        $iterator = new \RecursiveIteratorIterator($this);
        $iterator->rewind();

        $list = array();

        while ($iterator->valid()) {
            $list[$iterator->key()] = $iterator->current();
            $iterator->next();
        }

        if ($order === 'asc') {
            ksort($list);
        } else {
            krsort($list);
        }

        return $list;
    }
}
