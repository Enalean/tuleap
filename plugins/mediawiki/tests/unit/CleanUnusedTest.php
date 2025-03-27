<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use Backend;
use ColinODell\PsrTestLogger\TestLogger;
use MediawikiDao;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use Tuleap\Mediawiki\Maintenance\CleanUnused;
use Tuleap\Mediawiki\Maintenance\CleanUnusedDao;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CleanUnusedTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CleanUnusedDao&MockObject $dao;
    private ProjectManager&MockObject $project_manager;
    private Backend&MockObject $backend;
    private MediawikiDao&MockObject $media_wiki_dao;
    private CleanUnused $clean_unused;
    private TestLogger $logger;
    private MediawikiDataDir&MockObject $data_dir;

    public function setUp(): void
    {
        $this->logger = new TestLogger();

        $this->project_manager = $this->createMock(ProjectManager::class);

        $this->dao = $this->createMock(CleanUnusedDao::class);
        $this->initDao();

        $this->backend        = $this->createMock(Backend::class);
        $this->media_wiki_dao = $this->createMock(MediawikiDao::class);
        $this->data_dir       = $this->createMock(MediawikiDataDir::class);

        $this->clean_unused = new CleanUnused(
            $this->logger,
            $this->dao,
            $this->project_manager,
            $this->backend,
            $this->media_wiki_dao,
            $this->data_dir
        );
    }

    private function assertCommonLogs(): void
    {
        self::assertTrue($this->logger->hasInfo('[MW Purge] Start purge'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Start purge of orphan bases'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] End purge of orphan bases'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Start purge of unused services'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Purge of unused services completed'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Purge completed'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] x database(s) deleted'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] x table(s) deleted in central DB'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] 0 directories deleted'));
    }

    private function initDao(): void
    {
        $this->dao->expects($this->once())->method('setLogger');
        $this->dao->expects($this->once())->method('getDeletedDatabasesCount')->willReturn('x');
        $this->dao->expects($this->once())->method('getDeletedTablesCount')->willReturn('x');
    }

    public function testPurgeUsedServicesEmptyWikiForAllProjectsExceptTemplateTwoPurgeWhenEmpty(): void
    {
        $project_1 = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project_1')->build();
        $project_2 = ProjectTestBuilder::aProject()->withId(102)->withUnixName('project_2')->build();
        $project_3 = ProjectTestBuilder::aProject()->withId(103)->withUnixName('project_3')->build();

        $this->project_manager->method('getProject')->willReturnCallback(static fn (int $project_id) => match ($project_id) {
            101 => $project_1,
            102 => $project_2,
            103 => $project_3,
        });

        $this->dao->method('getMediawikiDatabasesInUsedServices')
            ->willReturn(
                [
                    [
                        'project_id' => 101,
                        'database_name' => 'mediawiki_101',
                    ],
                    [
                        'project_id' => 102,
                        'database_name' => 'mediawiki_102',
                    ],
                    [
                        'project_id' => 103,
                        'database_name' => 'mediawiki_103',
                    ],
                ]
            );

        $this->dao->expects($this->once())->method('getMediawikiDatabaseInUnusedServices')->willReturn([]);
        $this->dao->expects($this->once())->method('getAllMediawikiBasesNotReferenced')->willReturn([]);

        $this->data_dir->expects(self::exactly(2))
            ->method('getMediawikiDir')
            ->willReturnCallback(static fn (Project $project) => match ($project) {
                $project_2 => 'path/to/mediawiki_102',
                $project_3 => 'path/to/mediawiki_103',
            });

        $this->dao->expects(self::exactly(2))
            ->method('desactivateService')
            ->willReturnCallback(static fn (mixed $project_id, bool $dry_run) => match (true) {
                $dry_run === false && ((int) $project_id === 102 || (int) $project_id === 103) => true,
            });

        $this->media_wiki_dao->expects(self::exactly(3))
            ->method('getMediawikiPagesNumberOfAProject')
            ->willReturnCallback(static fn (Project $project) => match ($project) {
                $project_1 => ['result' => 2],
                $project_2, $project_3 => ['result' => 0],
            });

        $this->dao->expects(self::exactly(2))->method('purge');

        $this->clean_unused->purge(false, [], true, null);

        $this->assertCommonLogs();
        self::assertTrue($this->logger->hasInfo('[MW Purge] Start purge of used but empty mediawiki on projects which are not defined as template'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] End of purge of used but empty mediawiki on projects which are not defined as template'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Found candidate mediawiki_102'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Found candidate mediawiki_103'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Delete data dir'));
    }

    public function testPurgeUsedServicesEmptyWikiForAllProjectsExceptTemplateOnePurgeWhenEmptyWithOneTemplate(): void
    {
        $project_1 = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project_1')->build();
        $project_2 = ProjectTestBuilder::aProject()->withId(102)->withUnixName('project_2')->withTypeTemplate()->build();
        $project_3 = ProjectTestBuilder::aProject()->withId(103)->withUnixName('project_3')->build();

        $this->project_manager->method('getProject')->willReturnCallback(static fn (int $project_id) => match ($project_id) {
            101 => $project_1,
            102 => $project_2,
            103 => $project_3,
        });

        $this->dao->expects($this->once())->method('getMediawikiDatabasesInUsedServices')
            ->willReturn(
                [
                    [
                        'project_id' => 101,
                        'database_name' => 'mediawiki_101',
                    ],
                    [
                        'project_id' => 102,
                        'database_name' => 'mediawiki_102',
                    ],
                    [
                        'project_id' => 103,
                        'database_name' => 'mediawiki_103',
                    ],
                ]
            );

        $this->dao->expects($this->once())->method('getMediawikiDatabaseInUnusedServices')->willReturn([]);
        $this->dao->expects($this->once())->method('getAllMediawikiBasesNotReferenced')->willReturn([]);

        $this->data_dir->expects($this->once())->method('getMediawikiDir')->with($project_3)->willReturn('path/to/mediawiki_103');

        $this->dao->expects($this->once())->method('desactivateService')->with(103, false);

        $this->media_wiki_dao->expects(self::exactly(3))
            ->method('getMediawikiPagesNumberOfAProject')
            ->willReturnCallback(static fn (Project $project) => match ($project) {
                $project_1 => ['result' => 2],
                $project_2, $project_3 => ['result' => 0],
            });

        $this->dao->expects($this->once())->method('purge');

        $this->clean_unused->purge(false, [], true, null);

        $this->assertCommonLogs();
        self::assertTrue($this->logger->hasInfo('[MW Purge] Start purge of used but empty mediawiki on projects which are not defined as template'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] End of purge of used but empty mediawiki on projects which are not defined as template'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Found candidate mediawiki_103'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Delete data dir'));
        self::assertTrue($this->logger->hasWarning('[MW Purge] Project project_2 (102) is a template. Skipped.'));
    }

    public function testPurgeUnusedServicesWithAGivenProjectShouldForceIt(): void
    {
        $project_1 = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project_1')->build();
        $project_2 = ProjectTestBuilder::aProject()->withId(102)->withUnixName('project_2')->build();
        $project_3 = ProjectTestBuilder::aProject()->withId(103)->withUnixName('project_3')->build();

        $this->project_manager->method('getProject')->willReturnCallback(static fn (int $project_id) => match ($project_id) {
            101 => $project_1,
            102 => $project_2,
            103 => $project_3,
        });

        $this->dao->expects($this->once())->method('getMediawikiDatabasesInUsedServices')->willReturn([]);
        $this->dao->expects($this->once())
            ->method('getMediawikiDatabaseInUnusedServices')
            ->willReturn(
                [
                    [
                        'project_id' => 103,
                        'database_name' => 'mediawiki_103',
                    ],
                ]
            );
        $this->dao->expects($this->once())->method('getAllMediawikiBasesNotReferenced')->willReturn([]);

        $path = 'path/to/mediawiki_103';

        $this->data_dir->expects($this->once())->method('getMediawikiDir')->with($project_3)->willReturn($path);
        $this->backend->method('recurseDeleteInDir')->with($path);

        $this->media_wiki_dao->expects($this->once())
            ->method('getMediawikiPagesNumberOfAProject')->with($project_3)->willReturn(['result' => 0]);

        $this->dao->expects($this->once())->method('purge');

        $this->clean_unused->purge(false, [103], false, null);

        $this->assertCommonLogs();
        self::assertTrue($this->logger->hasInfo('[MW Purge] Found candidate mediawiki_103'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Start purge of used but empty mediawiki'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] End of purge of used but empty mediawiki'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Delete data dir'));
    }

    public function testPurgeUsedServicesEmptyWikiForAllProjectsExceptTemplateOnePurgeWhenEmptyWithOneTemplateWithLimit(): void
    {
        $project_1 = ProjectTestBuilder::aProject()->withId(101)->withUnixName('project_1')->build();
        $project_2 = ProjectTestBuilder::aProject()->withId(102)->withUnixName('project_2')->withTypeTemplate()->build();
        $project_3 = ProjectTestBuilder::aProject()->withId(103)->withUnixName('project_3')->build();

        $this->project_manager->method('getProject')->willReturnCallback(static fn (int $project_id) => match ($project_id) {
            101 => $project_1,
            102 => $project_2,
            103 => $project_3,
        });

        $this->dao->expects($this->once())
            ->method('getMediawikiDatabasesInUsedServices')
            ->willReturn(
                [
                    [
                        'project_id' => 101,
                        'database_name' => 'mediawiki_101',
                    ],
                    [
                        'project_id' => 102,
                        'database_name' => 'mediawiki_102',
                    ],
                    [
                        'project_id' => 103,
                        'database_name' => 'mediawiki_103',
                    ],
                ]
            );

        $this->dao->expects($this->once())->method('getMediawikiDatabaseInUnusedServices')->willReturn([]);
        $this->dao->expects($this->once())->method('getAllMediawikiBasesNotReferenced')->willReturn([]);

        $this->data_dir->expects($this->once())->method('getMediawikiDir')->with($project_3)->willReturn('path/to/mediawiki_103');

        $this->dao->expects($this->once())->method('desactivateService')->with(103, false);

        $this->media_wiki_dao->expects(self::exactly(3))
            ->method('getMediawikiPagesNumberOfAProject')
            ->willReturnCallback(static fn (Project $project) => match ($project) {
                $project_1 => ['result' => 2],
                $project_2, $project_3 => ['result' => 0],
            });

        $this->dao->expects($this->once())->method('purge');

        $this->clean_unused->purge(false, [], true, 3);

        $this->assertCommonLogs();
        self::assertTrue($this->logger->hasInfo('[MW Purge] Start purge of 3 used but empty mediawiki on projects which are not defined as template'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] End of purge of used but empty mediawiki on projects which are not defined as template'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Found candidate mediawiki_103'));
        self::assertTrue($this->logger->hasInfo('[MW Purge] Delete data dir'));
        self::assertTrue($this->logger->hasWarning('[MW Purge] Project project_2 (102) is a template. Skipped.'));
    }
}
