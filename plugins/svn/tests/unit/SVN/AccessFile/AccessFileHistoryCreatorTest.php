<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\SVN\AccessControl;

use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use SVN_AccessFile_Writer;
use Tuleap\GlobalLanguageMock;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVNCore\Repository;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class AccessFileHistoryCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SVN_AccessFile_Writer
     */
    private $access_file_writer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectHistoryFormatter
     */
    private $project_history_formatter;
    /**
     * @var \BackendSVN&\PHPUnit\Framework\MockObject\MockObject
     */
    private $backend_svn;
    private bool $globals_svnaccess_set_initially;
    private bool $globals_svngroups_set_initially;
    private AccessFileHistoryDao&MockObject $access_file_dao;
    private Repository $repository;
    private AccessFileHistoryCreator $creator;
    /**
     * @var AccessFileHistoryFactory&MockObject
     */
    private $access_file_factory;

    public function setUp(): void
    {
        $this->globals_svnaccess_set_initially = isset($GLOBALS['SVNACCESS']);
        $this->globals_svngroups_set_initially = isset($GLOBALS['SVNGROUPS']);

        $this->access_file_dao           = $this->createMock(AccessFileHistoryDao::class);
        $this->access_file_factory       = $this->createMock(AccessFileHistoryFactory::class);
        $this->project_history_formatter = $this->createMock(ProjectHistoryFormatter::class);
        $this->project_history_dao       = $this->createMock(ProjectHistoryDao::class);
        $this->backend_svn               = $this->createMock(\BackendSVN::class);

        $this->creator = new AccessFileHistoryCreator(
            $this->access_file_dao,
            $this->access_file_factory,
            $this->project_history_dao,
            $this->project_history_formatter,
            $this->backend_svn
        );

        $project          = ProjectTestBuilder::aProject()->withId(100)->build();
        $this->repository = SvnRepository::buildActiveRepository(1, 'repo name', $project);

        $access_file_history = new NullAccessFileHistory($this->repository);
        $this->access_file_factory->method('getLastVersion')->with($this->repository)->willReturn($access_file_history);

        $this->access_file_writer = $this->createMock(SVN_AccessFile_Writer::class);

        ForgeConfig::set('svn_root_file', 'svn_root_file');
    }

    protected function tearDown(): void
    {
        if (! $this->globals_svnaccess_set_initially) {
            unset($GLOBALS['SVNACCESS']);
        }
        if (! $this->globals_svngroups_set_initially) {
            unset($GLOBALS['SVNGROUPS']);
        }
    }

    public function testItUpdatesAccessFile(): void
    {
        $new_access_file     = "[/tags]\n@members = r\n";
        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        $this->access_file_factory->method('getCurrentVersion')->with($this->repository)->willReturn($current_access_file);
        $this->access_file_dao->expects(self::once())->method('create')->willReturn(true);

        $this->project_history_formatter->expects(self::once())->method('getAccessFileHistory');
        $this->project_history_dao->expects(self::once())->method('groupAddHistory');

        $this->access_file_writer->expects(self::once())->method('write_with_defaults')->willReturn(true);

        $this->creator->create($this->repository, $new_access_file, time(), $this->access_file_writer);
    }

    public function testItThrowsAnExceptionWhenAccessFileSaveFailed(): void
    {
        $new_access_file     = "[/tags]\n@members = r\n";
        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );


        $this->access_file_factory->method('getCurrentVersion')->with($this->repository)->willReturn($current_access_file);
        $this->access_file_dao->expects(self::once())->method('create')->willReturn(true);

        $this->project_history_formatter->expects(self::once())->method('getAccessFileHistory');
        $this->project_history_dao->expects(self::once())->method('groupAddHistory');

        $this->access_file_writer->expects(self::once())->method('write_with_defaults')->willReturn(false);
        $this->access_file_writer->expects(self::once())->method('isErrorFile')->willReturn(true);

        $this->expectException(CannotCreateAccessFileHistoryException::class);

        $this->creator->create($this->repository, $new_access_file, time(), $this->access_file_writer);
    }

    public function testItCanForceCompleteAcessFileGeneration(): void
    {
        $new_access_file     = "[/tags]\n@members = r\n";
        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        $default_block = <<<EOT
# BEGIN CODENDI DEFAULT SETTINGS - DO NOT REMOVE
[groups]
members = userA, userB


[/]
* = r
@members = rw
# END CODENDI DEFAULT SETTINGS

EOT;

        $this->backend_svn->method('exportSVNAccessFileDefaultBloc')->willReturn($default_block);

        $this->access_file_factory->method('getCurrentVersion')->with($this->repository)->willReturn($current_access_file);
        $this->access_file_dao->expects(self::once())->method('create')->willReturn(true);

        $this->project_history_formatter->expects(self::once())->method('getAccessFileHistory');
        $this->project_history_dao->expects(self::once())->method('groupAddHistory');

        $this->access_file_writer->expects(self::once())->method('write_with_defaults')->willReturn(true);

        $this->creator->create($this->repository, $new_access_file, time(), $this->access_file_writer);
    }
}
