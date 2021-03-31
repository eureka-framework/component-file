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
 * File validator interface. Define validate method to implement to validate current line if necessary
 *
 * @author Romain Cottard
 */
interface FileValidatorInterface
{
    /**
     * Validate data.
     *
     * @param  mixed $line
     * @return mixed Return line data
     * @throws \RuntimeException
     */
    public function validate($line);
}
