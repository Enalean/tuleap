<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\Project\Dashboard;

use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RoadmapDashboardCreatorTest extends TestCase
{
    public function testItCreatesRoadmapDashboardIfEpicTrackerHasTimeframeSemantic(): void
    {
        $xml_project = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="story"></tracker>
                    <tracker id="epic">
                        <semantics>
                            <semantic type="timeframe" />
                        </semantics>
                    </tracker>
                </trackers>
            </project>
        ');

        $xml_dashboards = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <dashboards />
        ');

        $jira_issue_types = [
            new IssueType(
                'story',
                'story',
                false,
            ),
            new IssueType(
                'epic',
                'epic',
                false,
            ),
        ];

        $jira_epic_issue_type = 'epic';

        (new RoadmapDashboardCreator())->createRoadmapDashboard(
            $xml_project,
            $xml_dashboards,
            $jira_issue_types,
            $jira_epic_issue_type,
            new NullLogger()
        );

        self::assertTrue(isset($xml_dashboards->dashboard));
        self::assertSame('Roadmap', (string) $xml_dashboards->dashboard['name']);

        self::assertTrue(isset($xml_dashboards->dashboard->line));
        self::assertTrue(isset($xml_dashboards->dashboard->line->column));
        self::assertTrue(isset($xml_dashboards->dashboard->line->column->widget));
        self::assertSame('plugin_roadmap_project_widget', (string) $xml_dashboards->dashboard->line->column->widget['name']);
    }

    public function testItDoesNotCreateRoadmapDashboardIfEpicTrackerNotFound(): void
    {
        $xml_project = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="story"></tracker>
                    <tracker id="tasks">
                        <semantics>
                            <semantic type="timeframe" />
                        </semantics>
                    </tracker>
                </trackers>
            </project>
        ');

        $xml_dashboards = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <dashboards />
        ');

        $jira_issue_types = [
            new IssueType(
                'story',
                'story',
                false,
            ),
            new IssueType(
                'epic',
                'epic',
                false,
            ),
        ];

        $jira_epic_issue_type = 'epic';

        (new RoadmapDashboardCreator())->createRoadmapDashboard(
            $xml_project,
            $xml_dashboards,
            $jira_issue_types,
            $jira_epic_issue_type,
            new NullLogger()
        );

        self::assertFalse(isset($xml_dashboards->dashboard));
    }

    public function testItDoesNotCreateRoadmapDashboardIfEpicTrackerDoesNotHaveTimeframeSemantic(): void
    {
        $xml_project = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="story"></tracker>
                    <tracker id="epic">
                        <semantics>
                            <semantic type="title" />
                        </semantics>
                    </tracker>
                </trackers>
            </project>
        ');

        $xml_dashboards = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <dashboards />
        ');

        $jira_issue_types = [
            new IssueType(
                'story',
                'story',
                false,
            ),
            new IssueType(
                'epic',
                'epic',
                false,
            ),
        ];

        $jira_epic_issue_type = 'epic';

        (new RoadmapDashboardCreator())->createRoadmapDashboard(
            $xml_project,
            $xml_dashboards,
            $jira_issue_types,
            $jira_epic_issue_type,
            new NullLogger()
        );

        self::assertFalse(isset($xml_dashboards->dashboard));
    }
}
