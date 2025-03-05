<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance;

use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Queue\QueueTask;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EnqueueTaskStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectStatusHandlerTest extends TestCase
{
    /**
     * @param \Project::STATUS_ACTIVE|\Project::STATUS_SUSPENDED|\Project::STATUS_DELETED $status
     */
    #[DataProvider('getTestData')]
    public function testWhenProjectIsSuspendedTheSuspensionEventIsEmitted(?QueueTask $expected_task, \Project $project, string $status): void
    {
        $queue = new EnqueueTaskStub();

        $handler = new ProjectStatusHandler($queue);
        $handler->handle($project, $status);

        self::assertEquals($expected_task, $queue->queue_task);
    }

    public static function getTestData(): iterable
    {
        $project_with_mediawiki_service           = ProjectTestBuilder::aProject()->withUsedService(MediawikiStandaloneService::SERVICE_SHORTNAME)->build();
        $suspended_project_with_mediawiki_service = ProjectTestBuilder::aProject()
            ->withUsedService(MediawikiStandaloneService::SERVICE_SHORTNAME)
            ->withStatusSuspended()
            ->build();
        $project_without_service                  = ProjectTestBuilder::aProject()->withoutServices()->build();
        return [
            'when project is suspended, suspend task is emitted' => [
                'expected_task' => new SuspendInstanceTask($project_with_mediawiki_service),
                'project' => $project_with_mediawiki_service,
                'status' => \Project::STATUS_SUSPENDED,
            ],
            'when project is activated, resume task is emitted' => [
                'expected_task' => new ResumeInstanceTask($suspended_project_with_mediawiki_service),
                'project' => $suspended_project_with_mediawiki_service,
                'status' => \Project::STATUS_ACTIVE,
            ],
            'when project doesnt have mediawiki service, suspend does nothing' => [
                'expected_task' => null,
                'project' => $project_without_service,
                'status' => \Project::STATUS_SUSPENDED,
            ],
            'when project is deleted, delete task is emitted' => [
                'expected_task' => new DeleteInstanceTask($project_with_mediawiki_service),
                'project' => $project_with_mediawiki_service,
                'status' => \Project::STATUS_DELETED,
            ],
        ];
    }
}
