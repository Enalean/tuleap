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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProjectMilestones\Widget;

use Codendi_Request;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Widget\Event\ConfigureAtXMLImport as ConfigureAtXMLImportEvent;
use Tuleap\XML\MappingsRegistry;

final class ConfigureAtXMLImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItDoesntCreateTheWidgetThatIsNotProjectMilestone(): void
    {
        $widget = new class extends \Widget {
            public string $param;
            public \Project $project;

            public function __construct()
            {
                parent::__construct(1);
            }

            public function create(Codendi_Request $request)
            {
                throw new \Exception('Should not be called because it\'s not project milestone plugin');
            }
        };

        (new ConfigureAtXMLImport())(
            new ConfigureAtXMLImportEvent(
                $widget,
                new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />'),
                new MappingsRegistry(),
                ProjectTestBuilder::aProject()->build()
            )
        );

        // The test fails if an exception is thrown
        $this->expectNotToPerformAssertions();
    }

    public function testItCreatesTheWidget(): void
    {
        $widget = new class extends \Widget {
            public string $param;
            public \Project $project;

            public function __construct()
            {
                parent::__construct(DashboardProjectMilestones::NAME);
            }

            public function create(Codendi_Request $request)
            {
                $this->param   = $request->get(ProjectMilestonesWidgetRetriever::PARAM_SELECTED_PROJECT);
                $this->project = $request->get('project');
            }
        };

        (new ConfigureAtXMLImport())(
            new ConfigureAtXMLImportEvent(
                $widget,
                new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />'),
                new MappingsRegistry(),
                ProjectTestBuilder::aProject()->build()
            )
        );

        self::assertSame(ProjectMilestonesWidgetRetriever::VALUE_SELECTED_PROJECT_SELF, $widget->param);
        self::assertEquals(ProjectTestBuilder::aProject()->build(), $widget->project);
    }
}
