<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\ProFTPd\Xferlog\Dao;

final class FileImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserDao
     */
    private $user_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = $this->createMock(Dao::class);
        $this->parser          = $this->getMockBuilder('Tuleap\ProFTPd\Xferlog\Parser')->getMock();
        $this->user_manager    = $this->getMockBuilder('UserManager')->disableOriginalConstructor()->getMock();
        $this->project_manager = $this->getMockBuilder('ProjectManager')->disableOriginalConstructor()->getMock();
        $this->user_dao        = $this->getMockBuilder(UserDao::class)->disableOriginalConstructor()->getMock();

        $user    = \Tuleap\Test\Builders\UserTestBuilder::aUser()->build();
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build();

        $this->user_manager->expects($this->any())->method('getUserByUserName')->will($this->returnValue($user));
        $this->project_manager->expects($this->any())->method('getProjectByUnixName')->will($this->returnValue($project));

        $this->file_importer = new Tuleap\ProFTPd\Xferlog\FileImporter(
            $this->dao,
            $this->parser,
            $this->user_manager,
            $this->project_manager,
            $this->user_dao,
            '/bla'
        );
    }

    public function testParseAndImportLines(): void
    {
        $this->dao->method('searchLatestEntryTimestamp')->willReturn(0);
        $this->parser
            ->expects($this->exactly(5))
            ->method('extract')
            ->will($this->returnValue($this->getMockBuilder('Tuleap\ProFTPd\Xferlog\Entry')->disableOriginalConstructor()->getMock()));

        $this->dao
            ->expects($this->exactly(5))
            ->method('store');

        $this->user_dao->expects($this->once())->method('storeLastAccessDate');

        $this->file_importer->import(__DIR__ . '/_fixtures/xferlog');
    }

    public function testItIgnoreOldLogs(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchLatestEntryTimestamp')
            ->willReturn(1389687000);

        $this->dao
            ->expects($this->exactly(3))
            ->method('store');

        $this->user_dao->expects(self::once())->method('storeLastAccessDate');

        $file_importer = new Tuleap\ProFTPd\Xferlog\FileImporter(
            $this->dao,
            new Tuleap\ProFTPd\Xferlog\Parser(),
            $this->user_manager,
            $this->project_manager,
            $this->user_dao,
            '/bla'
        );

        $file_importer->import(__DIR__ . '/_fixtures/xferlog');
    }
}
