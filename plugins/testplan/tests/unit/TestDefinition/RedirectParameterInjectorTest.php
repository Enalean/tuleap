<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan\TestDefinition;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RedirectParameterInjectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItDoesNotInjectAnythingIfThereIsNoBacklogItemIdInTheRequest(): void
    {
        $request  = new \Codendi_Request([], \Mockery::spy(\ProjectManager::class));
        $redirect = new \Tracker_Artifact_Redirect();

        (new RedirectParameterInjector())->inject($request, $redirect);

        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNotInjectAnythingIfThereIsNoMilestoneIdInTheRequest(): void
    {
        $request  = new \Codendi_Request(
            [
                'ttm_backlog_item_id' => 123,
            ],
            \Mockery::spy(\ProjectManager::class)
        );
        $redirect = new \Tracker_Artifact_Redirect();

        (new RedirectParameterInjector())->inject($request, $redirect);

        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testInject(): void
    {
        $request  = new \Codendi_Request(
            [
                'ttm_backlog_item_id' => 123,
                'ttm_milestone_id'    => 42,
            ],
            \Mockery::spy(\ProjectManager::class)
        );
        $redirect = new \Tracker_Artifact_Redirect();

        (new RedirectParameterInjector())->inject($request, $redirect);

        $this->assertEquals(
            [
                'ttm_backlog_item_id' => 123,
                'ttm_milestone_id'    => 42
            ],
            $redirect->query_parameters
        );
    }
}
