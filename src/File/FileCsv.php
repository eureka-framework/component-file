<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\File\File;

use Eureka\Component\File\File\Exception\FileNotFoundException;

/**
 * Class to manipulate files. Extends File (and SplFile*)
 *
 * @author Romain Cottard
 */
class FileCsv extends File implements FileValidatorInterface
{
    const COMMON_DELIMITERS    = [',', ';', '|', "\t"];
    protected array $header    = [];
    protected bool $skipHeader = false;

    /**
     * Overridden class constructor. Check if file is compressed, and use appropriate wrapper.
     * Force some flag for csv by default.
     *
     * @param string   $fileName The file to read.
     * @param string   $mode The mode in which to open the file. See fopen() for a list of allowed modes.
     * @param bool     $useIncludePath Whether to search in the include_path for filename.
     * @param resource $context A valid context resource created with stream_context_create().
     * @param bool     $isGzCompressed True if file is compressed using gzip (.gz).
     * @throws FileNotFoundException
     */
    public function __construct(string $fileName, $mode = 'r', $useIncludePath = false, $context = null, $isGzCompressed = false)
    {
        parent::__construct($fileName, $mode, $useIncludePath, $context, $isGzCompressed);

        $this->readAhead(true); // mandatory to skip empty line
        $this->skipEmptyLines(true);
        $this->dropNewLines(true);
        $this->readCsv(true);
    }

    /**
     * @param string[] $moreDelimiters
     */
    public function autoDetectDelimiter($moreDelimiters = [])
    {
        $columns = [];
        $delimiters = array_merge(self::COMMON_DELIMITERS, $moreDelimiters);
        $csvControl = $this->getCsvControl();

        $currentLine = $this->key();

        if ($currentLine > 0) {
            $this->seek(0);
            $currentHeader = $this->fgets();
            $this->seek($currentLine);
        } else {
            $currentHeader = $this->fgets();
            $this->seek(0);
        }

        foreach ($delimiters as $delimiter) {
            $columns[$delimiter] = count(str_getcsv($currentHeader, $delimiter, $csvControl[1], $csvControl[2]));
        }

        // pick delimiter which yields the more columns
        $delimiter = array_search(max($columns), $columns);

        // update csv control
        $csvControl[0] = $delimiter;
        $this->setCsvControl(...$csvControl);
    }

    /**
     * Skip Header file. Store it in header property.
     *
     * @param  bool $skipHeader
     * @return $this
     */
    public function skipHeader(bool $skipHeader = true): self
    {
        $this->skipHeader = $skipHeader;

        //~ Get Header content.
        $this->getHeader();

        //~ Go to the next line if we are on the first line.
        if ($this->key() === 0 && $this->skipHeader) {
            $this->next();
        }

        return $this;
    }

    /**
     * Store & Get header data.
     *
     * @return array Header line data
     */
    public function getHeader(): array
    {
        if (!empty($this->header)) {
            return $this->header;
        }

        //~ Store current position
        $currentLine = $this->key();

        //~ Move to line only if current line is not first (optimization)
        if ($currentLine > 0) {
            $this->seek(0); // Go to the first line
            $this->header = $this->current(); // Get Header content (current first line)
            $this->seek($currentLine); // Go to the original position in file.
        } else {
            $this->header = $this->current();
        }

        return $this->header;
    }

    /**
     * Rewinds the file back to the first line.
     * Force next line when skip header is enabled.
     *
     * @return void
     */
    public function rewind(): void
    {
        parent::rewind();

        if ($this->skipHeader) {
            $this->next();
        }
    }

    /**
     * Validate csv array, meaning it can be mapped to a valid model object.
     *
     * @param  mixed $line
     * @return mixed Return line data
     * @throws \RuntimeException
     */
    public function validate($line): array
    {
        if (empty($line)) {
            throw new \RuntimeException('Empty array cannot be mapped.');
        }

        if (!is_array($line)) {
            throw new \UnexpectedValueException('Given data is not a valid array.');
        }

        return $line;
    }
}
