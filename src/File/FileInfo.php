<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\File\File;

/**
 * Class to manipulate files info. Extends SplFileInfo
 *
 * @author Romain Cottard
 */
class FileInfo extends \SplFileInfo
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
     * Remove file / directory
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function remove(): bool
    {
        if ($this->isDir() && $this->getFilename() === '..') {
            throw new \RuntimeException(__METHOD__ . '|Cannot remove parent directory !', 10001);
        }

        if ($this->isDir()) {
            $removed = rmdir($this->getPath());
        } else {
            $removed = unlink($this->getPathname());
        }

        if (!$removed) {
            throw new \RuntimeException(__METHOD__ . '|Cannot remove file / directory !', 10002);
        }

        return true;
    }
}
