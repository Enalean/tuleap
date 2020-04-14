<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class TmpWatchTest extends TestCase
{
    private $target_dir;

    protected function setUp(): void
    {
        $this->target_dir = vfsStream::setup()->url();
    }

    public function testItReportsAnErrorWhenDirectoryDoesntExist(): void
    {
        $tmp_watch = new TmpWatch($this->target_dir . '/foo', 5);

        $this->expectException(InvalidDirectoryException::class);
        $tmp_watch->run();
    }

    public function testItReportsAnErrorWhenDirectoryIsFalse(): void
    {
        $tmp_watch = new TmpWatch((string) false, 5);

        $this->expectException(InvalidDirectoryException::class);
        $tmp_watch->run();
    }

    public function testItReportsAnErrorWhenDirectoryIsNull(): void
    {
        $tmp_watch = new TmpWatch((string) null, 5);

        $this->expectException(InvalidDirectoryException::class);
        $tmp_watch->run();
    }

    public function testItRemovesFilesInDirectory(): void
    {
        mkdir($this->target_dir . '/foo', 0755, true);
        touch($this->target_dir . '/foo/bar', (new \DatetimeImmutable('5 hours ago'))->getTimestamp());

        $tmp_watch = new TmpWatch($this->target_dir . '/foo', 1);
        $tmp_watch->run();

        $this->assertFileDoesNotExist($this->target_dir . '/foo/bar');
    }

    public function testItDoesntRemovesFilesThatAreNotExpired(): void
    {
        mkdir($this->target_dir . '/foo', 0755, true);
        touch($this->target_dir . '/foo/bar', (new \DatetimeImmutable('5 hours ago'))->getTimestamp());

        $tmp_watch = new TmpWatch($this->target_dir . '/foo', 12);
        $tmp_watch->run();

        $this->assertFileExists($this->target_dir . '/foo/bar');
    }


    public function testItRemovesFilesWithLongTimes(): void
    {
        mkdir($this->target_dir . '/foo', 0755, true);
        touch($this->target_dir . '/foo/bar', (new \DatetimeImmutable('15 days ago'))->getTimestamp());

        $tmp_watch = new TmpWatch($this->target_dir . '/foo', 14 * 24);
        $tmp_watch->run();

        $this->assertFileDoesNotExist($this->target_dir . '/foo/bar');
    }

    public function testItRemovesSeveralFiles(): void
    {
        mkdir($this->target_dir . '/foo', 0755, true);

        touch($this->target_dir . '/foo/bar', (new \DatetimeImmutable('5 hours ago'))->getTimestamp());
        touch($this->target_dir . '/foo/baz', (new \DatetimeImmutable('6 hours ago'))->getTimestamp());
        touch($this->target_dir . '/foo/bur', (new \DatetimeImmutable('7 hours ago'))->getTimestamp());

        $tmp_watch = new TmpWatch($this->target_dir . '/foo', 1);
        $tmp_watch->run();

        $this->assertFileDoesNotExist($this->target_dir . '/foo/bar');
        $this->assertFileDoesNotExist($this->target_dir . '/foo/baz');
        $this->assertFileDoesNotExist($this->target_dir . '/foo/bur');
    }

    public function testItRemovesOnlyExpiredFiles(): void
    {
        mkdir($this->target_dir . '/foo', 0755, true);

        touch($this->target_dir . '/foo/bar', (new \DatetimeImmutable('5 hours ago'))->getTimestamp());
        touch($this->target_dir . '/foo/baz', (new \DatetimeImmutable('1 hour ago'))->getTimestamp());
        touch($this->target_dir . '/foo/bur', (new \DatetimeImmutable('7 hours ago'))->getTimestamp());

        $tmp_watch = new TmpWatch($this->target_dir . '/foo', 2);
        $tmp_watch->run();

        $this->assertFileDoesNotExist($this->target_dir . '/foo/bar');
        $this->assertFileExists($this->target_dir . '/foo/baz');
        $this->assertFileDoesNotExist($this->target_dir . '/foo/bur');
    }

    public function testItDoesntRemoveDirectories(): void
    {
        mkdir($this->target_dir . '/foo', 0755, true);

        mkdir($this->target_dir . '/foo/fii', 0755, true);
        touch($this->target_dir . '/foo/fii', (new \DatetimeImmutable('7 hours ago'))->getTimestamp());

        $tmp_watch = new TmpWatch($this->target_dir . '/foo', 2);
        $tmp_watch->run();

        $this->assertFileExists($this->target_dir . '/foo/fii');
    }
}
