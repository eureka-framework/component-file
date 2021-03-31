<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\File;

use Eureka\Component\File\Exception\DirectoryException;

/**
 * Class Directory
 *
 * @author Jean Pasdeloup
 */
class Path
{
    private const TRIM_CHARS = " \t\n\r\0\x0B" . DIRECTORY_SEPARATOR;

    /**
     * Trim '/' from the given dir + removes '/' duplicates + add a leading '/' => '/myPath' and not 'myPath/' or '/myPath/'
     *  be careful, empty path '/' becomes really empty '' to allow better concatenation
     *
     * @param string $path
     * @param bool   $withLeadingSeparator
     * @return string
     */
    public function normalize(string $path, bool $withLeadingSeparator = true): string
    {
        $path = trim($path, self::TRIM_CHARS);
        if (empty($path)) {
            return '';
        }

        return ($withLeadingSeparator ? DIRECTORY_SEPARATOR : '' ) .
            preg_replace('`(\\' . DIRECTORY_SEPARATOR . '){2,}`', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param string[] $elements
     * @return string
     */
    public function build(...$elements): string
    {
        $path = '';
        foreach ($elements as $element) {
            $path .= $this->normalize($element);
        }

        return $path;
    }

    /**
     * Get base path of given path
     *
     * @param string $path
     * @param int $levels
     * @param bool $fromEnd
     * @return string
     */
    public function dirname(string $path, int $levels = 1, bool $fromEnd = true): string
    {
        if ($fromEnd) {
            return dirname($this->normalize($path), $levels);
        }

        $paths = explode(DIRECTORY_SEPARATOR, $this->normalize($path));

        return $this->normalize(implode(DIRECTORY_SEPARATOR, array_slice($paths, 0, ($levels + 1))));
    }

    /**
     * Get the n element from a path
     *
     * @param string $path
     * @param int $levels
     * @param bool $fromEnd
     * @return string
     */
    public function element(string $path, int $levels = 1, bool $fromEnd = true): string
    {
        return basename($this->dirname($path, $levels, $fromEnd));
    }

    /**
     * Get relative directory ie /base/path/myDir => /myDir + check if consistent (correct base + 1 level only)
     *
     * @param string $fullPath
     * @param string $basePath
     * @param bool $allowOnlyOneLevel Is set, only one level of directory is allowed (can't have /base/path/myDir/mySubdir)
     * @return string
     */
    public function relative(string $fullPath, string $basePath, $allowOnlyOneLevel = false): string
    {
        $fullPath = $this->normalize($fullPath);
        $basePath = $this->normalize($basePath);

        //~ The given directory must start with pathDelivery
        if (strpos($fullPath, $basePath) !== 0) {
            throw new DirectoryException('Invalid directory, should start with ' . $basePath, 10010);
        }

        //~ Now remove this base path from full path to get relative path
        $subPath = str_replace($basePath, '', $fullPath);

        //~ And trim to remove lead/tail "/" chars
        $subPath = trim($subPath, DIRECTORY_SEPARATOR);

        //~ Check if there is no / left, it would mean it's a multi-level directory
        if ($allowOnlyOneLevel && strpos($subPath, DIRECTORY_SEPARATOR) !== false) {
            throw new DirectoryException("Invalid directory {$subPath} only one level after path path is allowed", 10011);
        }

        return $this->normalize($subPath);
    }

    /**
     * Join two path (root & relative) into one path.
     *
     * @param  string $rootPath
     * @param  string $relativePath
     * @param  bool $appendOnIncompatibility
     * @return string
     * @throws DirectoryException
     */
    public function join(string $rootPath, string $relativePath, bool $appendOnIncompatibility = false): string
    {
        $root     = explode(DIRECTORY_SEPARATOR, $this->normalize($rootPath, false));
        $relative = explode(DIRECTORY_SEPARATOR, $this->normalize($relativePath, false));

        $beforeJoin = [];
        $afterJoin  = [];

        $index      = 0;
        $startJoin  = false;
        foreach ($root as $element) {
            if (!$startJoin) {
                $beforeJoin[] = $element;
                if ($element === $relative[$index]) {
                    $startJoin = true; // Update start join status
                    $index++;
                }
                continue;
            }

            //~ Incompatibility case
            if ($element !== $relative[$index]) {
                //~ Throw an exception if no append action defined.
                if (!$appendOnIncompatibility) {
                    throw new DirectoryException("Cannot join {$rootPath} & {$relativePath}.", 10012);
                }

                //~ Rollback joined check, and continue as before join.
                $beforeJoin = array_merge($beforeJoin, $afterJoin);
                $afterJoin  = [];
                $startJoin  = false;
                $index      = 0;
            }

            $afterJoin[] = $element;
            $index++;
        }

        $joinedPath = array_merge($beforeJoin, $afterJoin, array_slice($relative, $index));

        return $this->normalize(implode(DIRECTORY_SEPARATOR, $joinedPath));
    }

    /**
     * @param string $filename
     * @codeCoverageIgnore
     */
    public function recursiveClearStatCache(string $filename): void
    {
        clearstatcache(true, $filename);
        if (!in_array(dirname($filename), [DIRECTORY_SEPARATOR, '.'])) {
            $this->recursiveClearStatCache(dirname($filename));
        }
    }
}
