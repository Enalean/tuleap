<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Workflow;

use Psr\Log\NullLogger;
use Tracker_RulesManager;
use Tracker_Workflow_Trigger_RulesManager;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAProxyAroundWorkflow(): void
    {
        $workflow = new \Workflow(
            $this->createStub(Tracker_RulesManager::class),
            $this->createStub(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger(new NullLogger(), 0),
            '123',
            444,
            '333',
            null,
            null
        );

        $proxy = WorkflowProxy::fromWorkflow($workflow);
        self::assertSame((int) $workflow->getId(), $proxy->getId());
    }
}
