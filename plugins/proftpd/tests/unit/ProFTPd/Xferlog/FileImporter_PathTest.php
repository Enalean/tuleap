<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

class FileImporter_PathTest extends \PHPUnit\Framework\TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = $this->getMockBuilder('Tuleap\ProFTPd\Xferlog\Dao')->disableOriginalConstructor()->getMock();
        $this->parser          = new Tuleap\ProFTPd\Xferlog\Parser();
        $this->user_manager    = $this->getMockBuilder('UserManager')->disableOriginalConstructor()->getMock();
        $this->project_manager = $this->getMockBuilder('ProjectManager')->disableOriginalConstructor()->getMock();
        $this->user_dao        = $this->getMockBuilder(UserDao::class)->disableOriginalConstructor()->getMock();

        $user    = $this->getMockBuilder('PFUser')->disableOriginalConstructor()->getMock();
        $this->project = $this->getMockBuilder('Project')->disableOriginalConstructor()->getMock();

        $this->user_manager->expects($this->any())->method('getUserByUserName')->will($this->returnValue($user));

        $this->file_importer = new Tuleap\ProFTPd\Xferlog\FileImporter(
            $this->dao,
            $this->parser,
            $this->user_manager,
            $this->project_manager,
            $this->user_dao,
            '/mnt/bigfile'
        );
    }

    public function testItFetchTheProjectNameWhenPathIsRelative()
    {
        $this->project_manager->expects($this->once())->method('getProjectByUnixName')->with($this->equalTo('gpig'))->will($this->returnValue($this->project));

        $this->file_importer->import(__DIR__ . '/_fixtures/xferlog_relative');
    }

    public function testItStoresRelativeFilePathWhenPathIsRelative()
    {
        $this->dao
            ->expects($this->once())
            ->method('store')
            ->with($this->anything(), $this->anything(), $this->callback(function (Tuleap\ProFTPd\Xferlog\Entry $entry) {
                if ($entry->filename == '/gpig/pbl') {
                    return true;
                }
                return false;
            }));

        $this->file_importer->import(__DIR__ . '/_fixtures/xferlog_relative');
    }


    public function testItFetchTheProjectNameWhenPathIsAbsolute()
    {
        $this->project_manager->expects($this->once())->method('getProjectByUnixName')->with($this->equalTo('gpig'))->will($this->returnValue($this->project));

        $this->file_importer->import(__DIR__ . '/_fixtures/xferlog_absolute');
    }

    public function testItStoresRelativeFilePathWhenPathIsAbsolute()
    {
        $this->dao
            ->expects($this->once())
            ->method('store')
            ->with($this->anything(), $this->anything(), $this->callback(function (Tuleap\ProFTPd\Xferlog\Entry $entry) {
                if ($entry->filename == '/gpig/bla') {
                    return true;
                }
                return false;
            }));

        $this->file_importer->import(__DIR__ . '/_fixtures/xferlog_absolute');
    }
}
