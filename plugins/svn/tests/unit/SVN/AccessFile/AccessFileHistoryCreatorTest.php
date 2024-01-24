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
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVNCore\Repository;
use Tuleap\SVNCore\SVNAccessFileDefaultBlock;
use Tuleap\SVNCore\SVNAccessFileDefaultBlockGeneratorInterface;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class AccessFileHistoryCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use TemporaryTestDirectory;
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectHistoryFormatter
     */
    private $project_history_formatter;
    private AccessFileHistoryDao&MockObject $access_file_dao;
    private Repository $repository;
    private AccessFileHistoryCreator $creator;
    /**
     * @var AccessFileHistoryFactory&MockObject
     */
    private $access_file_factory;

    public function setUp(): void
    {
        $this->access_file_dao           = $this->createMock(AccessFileHistoryDao::class);
        $this->access_file_factory       = $this->createMock(AccessFileHistoryFactory::class);
        $this->project_history_formatter = $this->createMock(ProjectHistoryFormatter::class);
        $this->project_history_dao       = $this->createMock(ProjectHistoryDao::class);
        $default_block_generator         = new class implements SVNAccessFileDefaultBlockGeneratorInterface {
            public function getDefaultBlock(Repository $repository): SVNAccessFileDefaultBlock
            {
                return new SVNAccessFileDefaultBlock('');
            }
        };

        $this->creator = new AccessFileHistoryCreator(
            $this->access_file_dao,
            $this->access_file_factory,
            $this->project_history_dao,
            $this->project_history_formatter,
            $default_block_generator,
        );

        $project          = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->repository = SvnRepository::buildActiveRepository(1, 'repo_name', $project);

        $fixture_dir = $this->getTmpDir();
        ForgeConfig::set('sys_data_dir', $fixture_dir);
        mkdir($fixture_dir . '/svn_plugin/101/repo_name', 0700, true);

        $access_file_history = new NullAccessFileHistory($this->repository);
        $this->access_file_factory->method('getLastVersion')->with($this->repository)->willReturn($access_file_history);

        ForgeConfig::set('svn_root_file', 'svn_root_file');
        ForgeConfig::set('sys_http_user', getmyuid());
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

        $this->creator->create($this->repository, $new_access_file, time());

        self::assertStringContainsString($new_access_file, file_get_contents($this->repository->getSystemPath() . '/.SVNAccessFile'));
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

        // Make it write somewhere there is no way write can succeed to force exception
        ForgeConfig::set('sys_data_dir', '/');

        $this->expectException(CannotCreateAccessFileHistoryException::class);

        $this->creator->create($this->repository, $new_access_file, time());
    }
}
