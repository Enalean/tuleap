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
use Logger;
use MediawikiDao;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use ProjectManager;
use Psr\Log\LogLevel;
use Tuleap\Mediawiki\Maintenance\CleanUnused;
use Tuleap\Mediawiki\Maintenance\CleanUnusedDao;

class CleanUnusedTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|CleanUnusedDao
     */
    private $dao;

    /**
     * @var Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\MockInterface|Backend
     */
    private $backend;

    /**
     * @var Mockery\MockInterface|MediawikiDao
     */
    private $media_wiki_dao;

    /**
     * @var CleanUnused
     */
    private $clean_unused;

    /**
     * @var Mockery\MockInterface|Logger
     */
    private $logger;

    /**
     * @var Mockery\MockInterface|MediawikiDataDir
     */
    private $data_dir;

    /**
     * @var Mockery\MockInterface|Project
     */
    private $project_1;

    /**
     * @var Mockery\MockInterface|Project
     */
    private $project_2;

    /**
     * @var Mockery\MockInterface|Project
     */
    private $project_3;

    public function setUp(): void
    {
        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->initLogger();

        $this->project_1 = Mockery::mock(Project::class);
        $this->project_2 = Mockery::mock(Project::class);
        $this->project_3 = Mockery::mock(Project::class);

        $this->project_1->shouldReceive("getID")->andReturn(101);
        $this->project_2->shouldReceive("getID")->andReturn(102);
        $this->project_3->shouldReceive("getID")->andReturn(103);

        $this->project_1->shouldReceive("getUnixName")->andReturn('project_1');
        $this->project_2->shouldReceive("getUnixName")->andReturn('project_2');
        $this->project_3->shouldReceive("getUnixName")->andReturn('project_3');

        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->project_manager->shouldReceive('getProject')->withArgs([101])->andReturn($this->project_1);
        $this->project_manager->shouldReceive('getProject')->withArgs([102])->andReturn($this->project_2);
        $this->project_manager->shouldReceive('getProject')->withArgs([103])->andReturn($this->project_3);

        $this->dao = Mockery::mock(CleanUnusedDao::class);
        $this->initDao();

        $this->backend        = Mockery::mock(Backend::class);
        $this->media_wiki_dao = Mockery::mock(MediawikiDao::class);
        $this->data_dir       = Mockery::mock(MediawikiDataDir::class);

        $this->clean_unused = new CleanUnused(
            $this->logger,
            $this->dao,
            $this->project_manager,
            $this->backend,
            $this->media_wiki_dao,
            $this->data_dir
        );
    }

    private function initLogger(): void
    {
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Start purge", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Start purge of orphan bases", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] End purge of orphan bases", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Start purge of unused services", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Purge of unused services completed", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Purge completed", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] x database(s) deleted", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] x table(s) deleted in central DB", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] 0 directories deleted", []])->once();
    }

    private function initDao(): void
    {
        $this->dao->shouldReceive('setLogger')->once();
        $this->dao->shouldReceive('getDeletedDatabasesCount')->andReturn('x')->once();
        $this->dao->shouldReceive('getDeletedTablesCount')->andReturn('x')->once();
    }

    public function testPurgeUsedServicesEmptyWikiForAllProjectsExceptTemplateTwoPurgeWhenEmpty(): void
    {
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Start purge of used but empty mediawiki on projects which are not defined as template", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] End of purge of used but empty mediawiki on projects which are not defined as template", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Found candidate mediawiki_102", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Found candidate mediawiki_103", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Delete data dir", []])->twice();

        $this->dao->shouldReceive('getMediawikiDatabasesInUsedServices')
            ->andReturn(
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

        $this->dao->shouldReceive('getMediawikiDatabaseInUnusedServices')->once()->andReturn([]);
        $this->dao->shouldReceive('getAllMediawikiBasesNotReferenced')->once()->andReturn([]);

        $this->project_2->shouldReceive("isTemplate")->once()->andReturnFalse();
        $this->project_3->shouldReceive("isTemplate")->once()->andReturnFalse();

        $this->data_dir->shouldReceive('getMediawikiDir')->withArgs([$this->project_2])->once()->andReturn("path/to/mediawiki_102");
        $this->data_dir->shouldReceive('getMediawikiDir')->withArgs([$this->project_3])->once()->andReturn("path/to/mediawiki_103");

        $this->dao->shouldReceive('desactivateService')->once()->withArgs([102, false]);
        $this->dao->shouldReceive('desactivateService')->once()->withArgs([103, false]);

        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_1)
            ->once()
            ->andReturn(
                ['result' => 2]
            );
        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_2)
            ->once()
            ->andReturn(
                ['result' => 0]
            );
        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_3)
            ->once()
            ->andReturn(
                ['result' => 0]
            );

        $this->dao->shouldReceive('purge')->twice();

        $this->clean_unused->purge(false, [], true, null);
    }

    public function testPurgeUsedServicesEmptyWikiForAllProjectsExceptTemplateOnePurgeWhenEmptyWithOneTemplate(): void
    {
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Start purge of used but empty mediawiki on projects which are not defined as template", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] End of purge of used but empty mediawiki on projects which are not defined as template", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Found candidate mediawiki_103", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Delete data dir", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::WARNING, "[MW Purge] Project project_2 (102) is a template. Skipped.", []])->once();

        $this->dao->shouldReceive('getMediawikiDatabasesInUsedServices')
            ->once()
            ->andReturn(
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

        $this->dao->shouldReceive('getMediawikiDatabaseInUnusedServices')->once()->andReturn([]);
        $this->dao->shouldReceive('getAllMediawikiBasesNotReferenced')->once()->andReturn([]);

        $this->project_2->shouldReceive("isTemplate")->once()->andReturn(true);
        $this->project_3->shouldReceive("isTemplate")->once()->andReturn(false);

        $this->data_dir->shouldReceive('getMediawikiDir')->withArgs([$this->project_3])->once()->andReturn("path/to/mediawiki_103");

        $this->dao->shouldReceive('desactivateService')->once()->withArgs([103, false]);

        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_1)
            ->once()
            ->andReturn(['result' => 2]);

        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_2)
            ->once()
            ->andReturn(['result' => 0]);

        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_3)
            ->once()
            ->andReturn(['result' => 0]);

        $this->dao->shouldReceive('purge')->once();

        $this->clean_unused->purge(false, [], true, null);
    }

    public function testPurgeUnusedServicesWithAGivenProjectShouldForceIt(): void
    {
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Found candidate mediawiki_103", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Start purge of used but empty mediawiki", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] End of purge of used but empty mediawiki", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Delete data dir", []])->once();

        $this->dao->shouldReceive('getMediawikiDatabasesInUsedServices')->once()->andReturn([]);
        $this->dao->shouldReceive('getMediawikiDatabaseInUnusedServices')->once()
            ->andReturn(
                [
                    [
                        'project_id' => 103,
                        'database_name' => 'mediawiki_103',
                    ],
                ]
            );
        $this->dao->shouldReceive('getAllMediawikiBasesNotReferenced')->once()->andReturn([]);

        $path = "path/to/mediawiki_103";

        $this->data_dir->shouldReceive('getMediawikiDir')->withArgs([$this->project_3])->once()->andReturn($path);
        $this->backend->shouldReceive('recurseDeleteInDir')->with($path);

        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')->with($this->project_3)->once()->andReturn(['result' => 0]);

        $this->dao->shouldReceive('purge')->once();

        $this->clean_unused->purge(false, [103], false, null);
    }

    public function testPurgeUsedServicesEmptyWikiForAllProjectsExceptTemplateOnePurgeWhenEmptyWithOneTemplateWithLimit(): void
    {
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Start purge of 3 used but empty mediawiki on projects which are not defined as template", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] End of purge of used but empty mediawiki on projects which are not defined as template", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Found candidate mediawiki_103", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::INFO, "[MW Purge] Delete data dir", []])->once();
        $this->logger->shouldReceive('log')->withArgs([LogLevel::WARNING, "[MW Purge] Project project_2 (102) is a template. Skipped.", []])->once();

        $this->dao->shouldReceive('getMediawikiDatabasesInUsedServices')
            ->once()
            ->andReturn(
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

        $this->dao->shouldReceive('getMediawikiDatabaseInUnusedServices')->once()->andReturn([]);
        $this->dao->shouldReceive('getAllMediawikiBasesNotReferenced')->once()->andReturn([]);

        $this->project_2->shouldReceive("isTemplate")->once()->andReturn(true);
        $this->project_3->shouldReceive("isTemplate")->once()->andReturn(false);

        $this->data_dir->shouldReceive('getMediawikiDir')->once()->withArgs([$this->project_3])->andReturn("path/to/mediawiki_103");

        $this->dao->shouldReceive('desactivateService')->once()->withArgs([103, false]);

        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_1)
            ->once()
            ->andReturn(['result' => 2]);

        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_2)
            ->once()
            ->andReturn(['result' => 0]);

        $this->media_wiki_dao->shouldReceive('getMediawikiPagesNumberOfAProject')
            ->with($this->project_3)
            ->once()
            ->andReturn(['result' => 0]);

        $this->dao->shouldReceive('purge')->once();

        $this->clean_unused->purge(false, [], true, 3);
    }
}
