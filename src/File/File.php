<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\File\File;

use Eureka\Component\File\Exception\FileException;
use Eureka\Component\File\Exception\FileNotFoundException;
use Eureka\Component\File\Exception\FilePermissionException;

/**
 * Class to manipulate files.
 *
 * @author Romain Cottard
 */
class File extends \SplFileObject implements FileInterface
{
    const BOM_CHARACTER = "\xEF\xBB\xBF";

    protected bool $isGzCompressed = false;
    protected bool $removeBOM = false;

    /**
     * Overridden class constructor. Check if file is compressed, and use appropriate wrapper
     *
     * @param string $fileName The file to read.
     * @param string $mode The mode in which to open the file. See fopen() for a list of allowed modes.
     * @param bool $useIncludePath Whether to search in the include_path for filename.
     * @param resource $context A valid context resource created with stream_context_create().
     * @param bool $isGzCompressed True if file is compressed using gzip (.gz).
     * @throws FileNotFoundException
     */
    public function __construct(string $fileName, string $mode = 'r', bool $useIncludePath = false, $context = null, bool $isGzCompressed = false)
    {
        $this->isGzCompressed = $isGzCompressed;

        if ($mode === 'r' && !file_exists($fileName)) {
            throw new FileNotFoundException("The file $fileName doesn't exist");
        }

        if ($this->isGzCompressed) {
            $fileName = 'compress.zlib://' . $fileName;
        }

        parent::__construct($fileName, $mode, $useIncludePath, $context);
    }

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
     * Overridden parent method.
     * When file is gz compressed, use gzip command to extract real file size if possible.
     * Otherwise, return an estimation of the size (*5 compressed size)
     *
     * @return int
     */
    public function getSize()
    {
        if (!$this->isGzCompressed) {
            return parent::getSize();
        }

        //~ Override for gz compressed files
        $file   = str_replace('compress.zlib://', '', $this->getPathname());
        $output = array();
        $return = null;
        $string = exec('gzip --list ' . escapeshellarg($file), $output, $return);

        if ($return === 0) {
            $stats = explode(' ', trim(preg_replace('`[ ]+`', ' ', $string)));
            $size  = (int) $stats[1];
        } else {
            //~ Estimation size
            $stats = stat($file);
            $size  = $stats['size'] * 5;
        }

        return $size;
    }

    /**
     * Enable / Disable specified flag.
     *
     * @param  int $flag Flag to enable / disable
     * @param  bool $enable
     * @return $this
     */
    protected function enableFlag(int $flag, $enable = true): File
    {
        $flags = $this->getFlags();

        if ($enable) {
            $flags = ($flags | $flag);
        } else {
            $flags = ($flags - ($flags & $flag));
        }

        $this->setFlags($flags);

        return $this;
    }

    /**
     * Enable / Disable skip empty lines when parse file.
     *
     * @param  boolean $skipEmptyLines Enable / Disable
     * @return $this
     */
    public function skipEmptyLines($skipEmptyLines = true): File
    {
        return $this->enableFlag(self::SKIP_EMPTY, $skipEmptyLines);
    }

    /**
     * Enable / Disable drop new lines at the end of a line.
     *
     * @param  boolean $dropNewLines Enable / Disable
     * @return $this
     */
    public function dropNewLines($dropNewLines = true): File
    {
        return $this->enableFlag(self::DROP_NEW_LINE, $dropNewLines);
    }

    /**
     * Enable / Disable read lines as CSV rows.
     *
     * @param  boolean $readCsv Enable / Disable
     * @return $this
     */
    public function readCsv($readCsv = true): File
    {
        return $this->enableFlag(self::READ_CSV, $readCsv);
    }

    /**
     * Enable / Disable read on rewind/next.
     *
     * @param  bool $readAhead Enable / Disable
     * @return $this
     */
    public function readAhead($readAhead = true): File
    {
        return $this->enableFlag(self::READ_AHEAD, $readAhead);
    }

    /**
     * @param bool $removeBOM
     */
    public function removeBOM($removeBOM = true): void
    {
        $this->removeBOM = $removeBOM;
    }

    /**
     * Remove file / directory
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function remove(): bool
    {
        if ($this->isDir() && $this->getPathname() === '..') {
            throw new FileException(__METHOD__ . '|Cannot remove parent directory !', 10001);
        }

        if ($this->isDir()) {
            $removed = rmdir($this->getPath());
        } else {
            $removed = unlink($this->getPathname());
        }

        if (!$removed) {
            throw new FileException(__METHOD__ . '|Cannot remove file / directory !', 10002);
        }

        return true;
    }

    /**
     * Return the number of lines in the file
     *
     * @return int
     */
    public function countLines(): int
    {
        $this->seek(PHP_INT_MAX);
        $lines = $this->key();
        $this->rewind();

        return $lines;
    }

    /**
     * @return array|false|string|string[]
     */
    public function current()
    {
        if (!$this->removeBOM || $this->key() > 0) {
            return parent::current();
        }

        $line = parent::current();
        if (is_array($line) && !empty($line)) {
            $line[0] = str_replace(static::BOM_CHARACTER, '', $line[0]);
        } else {
            $line = str_replace(static::BOM_CHARACTER, '', $line);
        }

        return $line;
    }

    /**
     * Fix chmod on file
     *
     * @param  string $file
     * @param  int    $mode
     * @return void
     * @throws \RuntimeException
     * @throws FilePermissionException
     */
    public static function chmod(string $file, $mode = 0644)
    {
        if (!file_exists($file)) {
            throw new \RuntimeException('File does not exist! (file: ' . $file . ')');
        }

        $ownerId   = fileowner($file);
        $currentId = posix_getuid();
        if ($currentId !== $ownerId || $ownerId === false) {
            throw new FilePermissionException("chmod can only be run by root or the owner! (file: ' . $file . ', owner_id: $ownerId, current_id: $currentId)", 10003);
        }

        if (!@chmod($file, $mode)) {
            throw new FilePermissionException('Cannot change mode for current file! (file: ' . $file . ')', 10004);
        }
    }

    /**
     * Change user group on file
     *
     * @param  string $file
     * @param  string $group
     * @return void
     * @throws \RuntimeException
     */
    public static function chgrp(string $file, $group = 'users'): void
    {
        if (!file_exists($file)) {
            throw new FileException('File does not exist! (file: ' . $file . ')', 10005);
        }

        if (!chgrp($file, $group)) {
            throw new FileException('Cannot change group for current file! (file: ' . $file . ')', 10006);
        }
    }
}
