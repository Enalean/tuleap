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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectHistoryDao;
use SVN_AccessFile_Writer;
use Tuleap\GlobalLanguageMock;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVN\Repository\Repository;

class AccessFileHistoryCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SVN_AccessFile_Writer
     */
    private $access_file_writer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectHistoryFormatter
     */
    private $project_history_formatter;
    /**
     * @var \BackendSVN|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $backend_svn;
    /**
     * @var string
     */
    private $fixtures_dir;
    /**
     * @var bool
     */
    private $globals_svnaccess_set_initially;

    /**
     * @var bool
     */
    private $globals_svngroups_set_initially;
    /**
     * @var AccessFileHistoryDao
     */
    private $access_file_dao;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var AccessFileHistoryCreator
     */
    private $creator;
    /**
     * @var AccessFileHistoryFactory
     */
    private $access_file_factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->globals_svnaccess_set_initially = isset($GLOBALS['SVNACCESS']);
        $this->globals_svngroups_set_initially = isset($GLOBALS['SVNGROUPS']);

        $this->access_file_dao     = Mockery::mock(AccessFileHistoryDao::class);
        $this->access_file_factory = Mockery::mock(AccessFileHistoryFactory::class);
        $this->project_history_formatter = Mockery::mock(ProjectHistoryFormatter::class);
        $this->project_history_dao       = Mockery::mock(ProjectHistoryDao::class);
        $this->backend_svn = Mockery::mock(\BackendSVN::class);

        $this->creator = new AccessFileHistoryCreator(
            $this->access_file_dao,
            $this->access_file_factory,
            $this->project_history_dao,
            $this->project_history_formatter,
            $this->backend_svn
        );

        $this->repository = Mockery::mock(Repository::class);
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(100);
        $this->repository->shouldReceive('getProject')->andReturn($project);
        $this->repository->shouldReceive('getName')->andReturn("repo name");

        $this->fixtures_dir = "/tmp/test";

        $access_file_history = new NullAccessFileHistory($this->repository);
        $this->access_file_factory->shouldReceive('getLastVersion')->withArgs([$this->repository])->andReturn($access_file_history);

        $this->access_file_writer = Mockery::mock(SVN_AccessFile_Writer::class);

        ForgeConfig::set('svn_root_file', 'svn_root_file');
    }

    protected function tearDown(): void
    {
        if (!$this->globals_svnaccess_set_initially) {
            unset($GLOBALS['SVNACCESS']);
        }
        if (!$this->globals_svngroups_set_initially) {
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

        $this->repository->shouldReceive('getSystemPath')->andReturn($this->fixtures_dir);
        $this->access_file_factory->shouldReceive('getCurrentVersion')->withArgs([$this->repository])->andReturn($current_access_file);
        $this->access_file_dao->shouldReceive('create')->once()->andReturnTrue();

        $this->project_history_formatter->shouldReceive('getAccessFileHistory')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->once();

        $this->access_file_writer->shouldReceive('write_with_defaults')->once()->andReturnTrue();

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


        $this->repository->shouldReceive('getSystemPath')->andReturn("/incorrect/path");
        $this->access_file_factory->shouldReceive('getCurrentVersion')->withArgs([$this->repository])->andReturn($current_access_file);
        $this->access_file_dao->shouldReceive('create')->once()->andReturnTrue();

        $this->project_history_formatter->shouldReceive('getAccessFileHistory')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->once();

        $this->access_file_writer->shouldReceive('write_with_defaults')->once()->andReturnFalse();
        $this->access_file_writer->shouldReceive('isErrorFile')->once()->andReturnTrue();

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

        $this->backend_svn->shouldReceive('exportSVNAccessFileDefaultBloc')->andReturn($default_block);

        $this->repository->shouldReceive('getSystemPath')->andReturn($this->fixtures_dir);
        $this->access_file_factory->shouldReceive('getCurrentVersion')->withArgs([$this->repository])->andReturn($current_access_file);
        $this->access_file_dao->shouldReceive('create')->once()->andReturnTrue();

        $this->project_history_formatter->shouldReceive('getAccessFileHistory')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->once();

        $this->access_file_writer->shouldReceive('write_with_defaults')->once()->andReturnTrue();

        $this->creator->create($this->repository, $new_access_file, time(), $this->access_file_writer);
    }
}
