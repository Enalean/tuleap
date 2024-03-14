<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use ForgeConfig;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Registration\Template\Upload\ExtractArchiveAndCreateProject;
use Tuleap\Test\PHPUnit\TestCase;

final class WorkerEventProcessorFinderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testNothingWhenUnknownEvent(): void
    {
        self::assertTrue(
            (new WorkerEventProcessorFinder())->findFromWorkerEvent(
                new WorkerEvent(
                    new NullLogger(),
                    [
                        'event_name' => 'unknown',
                        'payload' => 'whatever',
                    ]
                )
            )->isNothing(),
        );
    }

    public function testExtractArchiveAndCreateProject(): void
    {
        ForgeConfig::set('codendi_dir', __DIR__ . '/../../../../');

        self::assertInstanceOf(
            ExtractArchiveAndCreateProject::class,
            (new WorkerEventProcessorFinder())->findFromWorkerEvent(
                new WorkerEvent(
                    new NullLogger(),
                    [
                        'event_name' => ExtractArchiveAndCreateProject::TOPIC,
                        'payload' => ['project_id' => 1001, 'filename' => '/test.zip', 'user_id' => 102],
                    ]
                )
            )->unwrapOr(null),
        );
    }
}
