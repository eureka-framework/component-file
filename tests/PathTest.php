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
use PHPUnit\Framework\TestCase;

/**
 * Class PathTest
 *
 * @author Romain Cottard
 */
class PathTest extends TestCase
{
    /**
     * @param string $path
     * @param bool $withLeadingSeparator
     * @param string $expected
     * @return void
     *
     * @dataProvider providerPathNormalized
     */
    public function testICanNormalizePath(string $path, bool $withLeadingSeparator, string $expected): void
    {
        $this->assertSame($expected, (new Path())->normalize($path, $withLeadingSeparator));
    }

    /**
     * @return void
     */
    public function testICanBuildPath(): void
    {
        $this->assertSame('/my/path/built', (new Path())->build('my', 'path', '/built'));
    }

    /**
     * @return void
     */
    public function testICanGetPathDirname(): void
    {
        $path = '/my/path/file';
        $this->assertSame('/my/path', (new Path())->dirname($path), 'With default parameters');
        $this->assertSame('/my', (new Path())->dirname('/my/path/file', 2), 'with levels = 2');
        $this->assertSame('/my', (new Path())->dirname('/my/path/file', 1, false), 'with levels = 1 from start');
    }

    /**
     * @return void
     */
    public function testICanGetPathElement(): void
    {
        $path = '/my/full/path/file';
        $this->assertSame('path', (new Path())->element($path), 'With default parameters');
        $this->assertSame('full', (new Path())->element($path, 2), 'with levels = 2');
        $this->assertSame('my', (new Path())->element($path, 1, false), 'with levels = 1 from start');
    }

    /**
     * @return void
     */
    public function testICanGetRelativePath(): void
    {
        $fullPath = '/my/full/path/file';
        $this->assertSame('/path/file', (new Path())->relative($fullPath, '/my/full/'));
        $this->assertSame('/full/path/file', (new Path())->relative($fullPath, '/my'));
    }

    /**
     * @return void
     */
    public function testAnExceptionIsThrownWhenITryToGetRelativePathWithAnInvalidBasePath(): void
    {
        $this->expectException(DirectoryException::class);
        $this->expectExceptionCode(10010);

        (new Path())->relative('/my/full/path/file', '/any/full/');
    }

    /**
     * @return void
     */
    public function testAnExceptionIsThrownWhenITryToGetRelativePathOnLongPathWithOnlyOneLevelWanted(): void
    {
        $this->expectException(DirectoryException::class);
        $this->expectExceptionCode(10011);

        (new Path())->relative('/my/full/path/file', '/my/full/', true);
    }

    /**
     * @return void
     */
    public function testICanJoinToPath(): void
    {
        $expected = '/my/full/path/final/joined';
        $root     = '/my/full/path';
        $end      = '/path/final/joined';
        $this->assertSame($expected, (new Path())->join($root, $end));

        $root     = '/my/full/path';
        $end      = '/final/joined';
        $this->assertSame($expected, (new Path())->join($root, $end));

        $expected = '/my/full/path/final/joined';
        $root     = '/my/full/path';
        $end      = '/my/full/path/final/joined';
        $this->assertSame($expected, (new Path())->join($root, $end));

        $expected = '/my/full/path/full/any/final/joined';
        $root     = '/my/full/path';
        $end      = '/my/full/any/final/joined';
        $this->assertSame($expected, (new Path())->join($root, $end, true));

        $this->expectException(DirectoryException::class);
        $this->expectExceptionCode(10012);
        $root     = '/my/full/path';
        $end      = '/my/full/any/final/joined';
        (new Path())->join($root, $end);
    }

    public function providerPathNormalized(): array
    {
        return [
            'normal path'                   => ['/my/path/to/normalize', true, '/my/path/to/normalize'],
            'normal path without ending /'  => ['/my/path/to/normalize/', true, '/my/path/to/normalize'],
            'normal path without leading /' => ['/my/path/to/normalize', false, 'my/path/to/normalize'],
            'empty path'                    => ['', true, ''],
        ];
    }
}
