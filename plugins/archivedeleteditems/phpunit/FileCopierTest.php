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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/bootstrap.php';

class FileCopierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $file_system;
    private $source_file;
    private $destination_file;

    protected function setUp(): void
    {
        $this->logger           = \Mockery::spy(LoggerInterface::class);
        $this->file_system      = vfsStream::setup();
        $this->source_file      = $this->file_system->url() . '/source_file';
        $this->destination_file = $this->file_system->url() . '/destination_file';
    }

    public function testItDoesNotCopyIfSourceDoesNotExist()
    {
        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy(
            $this->file_system->url() . '/does_not_exist',
            $this->destination_file,
            false
        );
        $this->assertFalse($is_copy_successful);
    }

    public function testItDoesNotCopyIfDestinationFileExist()
    {
        touch($this->source_file);
        touch($this->destination_file);

        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->source_file, $this->destination_file, false);
        $this->assertFalse($is_copy_successful);
    }

    public function testItReturnsTrueIfDestinationFileExistAndWeAreSkippingDuplicates()
    {
        touch($this->source_file);
        touch($this->destination_file);

        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->source_file, $this->destination_file, true);
        $this->assertTrue($is_copy_successful);
    }

    public function testItCopiesAFile(): void
    {
        $content = random_bytes(64);
        file_put_contents($this->source_file, $content);

        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->source_file, $this->destination_file, false);
        $this->assertTrue($is_copy_successful);
        $this->assertEquals($content, file_get_contents($this->source_file));
        $this->assertEquals($content, file_get_contents($this->destination_file));
    }
}
