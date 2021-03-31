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
 * Class to manipulate JSON files.
 *
 * @author Robin Seichais
 * @author Romain Cottard
 */
class FileJson extends File implements FileValidatorInterface
{
    /**
     * Overridden class constructor.
     * Check if file is compressed, and use appropriate wrapper
     *
     * @param string $fileName The file to read.
     * @param string $mode The mode in which to open the file. See fopen() for a list of allowed modes.
     * @param bool $useIncludePath Whether to search in the include_path for filename.
     * @param null $context A valid context resource created with stream_context_create().
     * @param bool $isGzCompressed True if file is compressed using gzip (.gz).
     */
    public function __construct(
        string $fileName,
        string $mode = 'r',
        $useIncludePath = false,
        $context = null,
        $isGzCompressed = false
    ) {
        parent::__construct($fileName, $mode, $useIncludePath, $context, $isGzCompressed);

        $this->readAhead()
            ->skipEmptyLines()
            ->dropNewLines()
        ;
    }

    /**
     * Override parent method. If mapper object is defined, return entity model object instead of line.
     *
     * @return \stdClass
     * @see SplFileObject::current()
     */
    public function current(): ?\stdClass
    {
        do {
            $line = (string) parent::current();
            $data = null;
            $continue = false;

            try {
                $data = $this->validate($line);
            } catch (\RuntimeException $exception) {
                $this->next();
                $continue = true;
            }
        } while ($continue && !$this->eof());

        return $data;
    }

    /**
     * Decode & validate line from file.
     *
     * @param mixed $line
     * @return \stdClass Json object
     * @throws \RuntimeException
     */
    public function validate($line): \stdClass
    {
        $json = json_decode(trim($line, ", \t\n\r\0\x0B"));

        if (json_last_error() !== JSON_ERROR_NONE || !($json instanceof \stdClass)) {
            throw new \RuntimeException('Invalid JSON string given.');
        }

        return $json;
    }

    /**
     * @param \JsonSerializable|array $json
     */
    public function writeJson($json): void
    {
        $this->fwrite(json_encode($json) . "\n");
    }
}
