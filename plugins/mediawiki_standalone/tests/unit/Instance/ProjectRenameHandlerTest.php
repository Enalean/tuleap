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

use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\QueueTask;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EnqueueTaskStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectRenameHandlerTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getTestData')]
    public function testRename(?QueueTask $expected_task, int $project_id, ProjectByIDFactory $project_factory): void
    {
        $queue = new EnqueueTaskStub();

        (new ProjectRenameHandler($queue, $project_factory))->handle($project_id, 'baz');

        self::assertEquals($expected_task, $queue->queue_task);
    }

    public static function getTestData(): iterable
    {
        $project_with_mediawiki_service = ProjectTestBuilder::aProject()->withUnixName('foo')->withId(120)->withUsedService(MediawikiStandaloneService::SERVICE_SHORTNAME)->build();
        $project_without_service        = ProjectTestBuilder::aProject()->withUnixName('bar')->withId(130)->withoutServices()->build();
        $project_factory                = ProjectByIDFactoryStub::buildWith($project_with_mediawiki_service, $project_without_service);

        return [
            'rename event will send rename task when mediawiki is used' => [
                'expected_task'   => new RenameInstanceTask($project_with_mediawiki_service, 'baz'),
                'project_id'      => 120,
                'project_factory' => $project_factory,
            ],
            'rename event will not be propagated toward mediawiki' => [
                'expected_task'   => null,
                'project_id'      => 130,
                'project_factory' => $project_factory,
            ],
            'rename event will not be propagated if project is not valid' => [
                'expected_task'   => null,
                'project_id'      => 101,
                'project_factory' => $project_factory,
            ],
        ];
    }
}
