<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\svn\Event;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\GlobalSVNPollution;

final class UpdateProjectAccessFileSystemEventTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    private const PROJECT_ID = 102;

    /**
     * @var \BackendSVN|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backend_svn;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;

    /**
     * @var UpdateProjectAccessFileSystemEvent
     */
    private $system_event;

    protected function setUp(): void
    {
        $this->backend_svn      = \Mockery::mock(\BackendSVN::class);
        \Backend::setInstance(\Backend::SVN, $this->backend_svn);
        $this->project_manager  = \Mockery::mock(\ProjectManager::class);
        $this->event_dispatcher = \Mockery::mock(EventDispatcherInterface::class);

        $this->system_event = new UpdateProjectAccessFileSystemEvent(
            12,
            \SystemEvent::TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES,
            \SystemEvent::OWNER_ROOT,
            (string) self::PROJECT_ID,
            \SystemEvent::PRIORITY_MEDIUM,
            \SystemEvent::STATUS_NEW,
            10,
            0,
            0,
            new \Psr\Log\NullLogger()
        );
        $this->system_event->injectDependencies($this->project_manager, $this->event_dispatcher);
    }

    protected function tearDown(): void
    {
        \Backend::clearInstances();
    }

    public function testCanProcessAccessFilesChanges(): void
    {
        $project = \Mockery::mock(\Project::class);
        $this->project_manager->shouldReceive('getProject')->with(self::PROJECT_ID)->andReturn($project);

        $project->shouldReceive('usesSVN')->andReturn(true);
        $project->shouldReceive('getSVNRootPath')->andReturn('/some/path/to/svn/root');
        $this->backend_svn->shouldReceive('updateSVNAccess')->once();

        $this->event_dispatcher->shouldReceive('dispatch')
            ->with(\Mockery::type(UpdateProjectAccessFilesEvent::class))->once();

        $this->system_event->process();
    }
}
