<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once('bootstrap.php');

class FileCopierTest extends \TuleapTestCase
{
    /**
     * @var ArchiveLogger
     */
    private $logger;
    /**
     * @var string
     */
    private $temporary_source_file;
    /**
     * @var string
     */
    private $temporary_destination_file;

    public function setUp()
    {
        parent::setUp();
        $this->logger                     = mock('Tuleap\ArchiveDeletedItems\ArchiveLogger');
        $this->temporary_source_file      = tempnam(sys_get_temp_dir(), 'source');
        $this->temporary_destination_file = tempnam(sys_get_temp_dir(), 'destination');
    }

    public function tearDown()
    {
        parent::tearDown();
        unlink($this->temporary_source_file);
        unlink($this->temporary_destination_file);
    }

    public function itDoesNotCopyIfSourceDoesNotExist()
    {
        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy('/file-do-not-exist', $this->temporary_destination_file, false);
        $this->assertFalse($is_copy_successful);
    }

    public function itDoesNotCopyIfDestinationFileExist()
    {
        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->temporary_source_file, $this->temporary_destination_file, false);
        $this->assertFalse($is_copy_successful);
    }

    public function itReturnsTrueIfDestinationFileExistAnsWeAreSkippingDuplicates()
    {
        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->temporary_source_file, $this->temporary_destination_file, true);
        $this->assertTrue($is_copy_successful);
    }

    public function itCopiesAFile()
    {
        $content = uniqid('FileCopierTest');
        file_put_contents($this->temporary_source_file, $content);
        unlink($this->temporary_destination_file);

        $file_copier        = new FileCopier($this->logger);
        $is_copy_successful = $file_copier->copy($this->temporary_source_file, $this->temporary_destination_file, false);
        $this->assertTrue($is_copy_successful);
        $this->assertEqual($content, file_get_contents($this->temporary_source_file));
        $this->assertEqual($content, file_get_contents($this->temporary_destination_file));
    }
}
