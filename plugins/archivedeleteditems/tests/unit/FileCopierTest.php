<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 */

namespace Tuleap\ArchiveDeletedItems;

use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

require_once __DIR__ . '/bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FileCopierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $file_system;
    private string $source_file;
    private string $destination_file;

    protected function setUp(): void
    {
        $this->logger           = new NullLogger();
        $this->file_system      = vfsStream::setup();
        $this->source_file      = $this->file_system->url() . '/source_file';
        $this->destination_file = $this->file_system->url() . '/destination_file';
    }

    public function testItDoesNotCopyIfSourceDoesNotExist(): void
    {
        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy(
            $this->file_system->url() . '/does_not_exist',
            $this->destination_file,
            false
        );
        self::assertFalse($is_copy_successful);
    }

    public function testItDoesNotCopyIfDestinationFileExist(): void
    {
        touch($this->source_file);
        touch($this->destination_file);

        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->source_file, $this->destination_file, false);
        self::assertFalse($is_copy_successful);
    }

    public function testItReturnsTrueIfDestinationFileExistAndWeAreSkippingDuplicates(): void
    {
        touch($this->source_file);
        touch($this->destination_file);

        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->source_file, $this->destination_file, true);
        self::assertTrue($is_copy_successful);
    }

    public function testItCopiesAFile(): void
    {
        $content = random_bytes(64);
        file_put_contents($this->source_file, $content);

        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->source_file, $this->destination_file, false);
        self::assertTrue($is_copy_successful);
        self::assertEquals($content, file_get_contents($this->source_file));
        self::assertEquals($content, file_get_contents($this->destination_file));
    }
}
