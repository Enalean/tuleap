<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\GraphOnTrackersV5\XML\Template;

use Tuleap\GraphOnTrackersV5\XML\XMLBarChart;
use Tuleap\GraphOnTrackersV5\XML\XMLGraphOnTrackerRenderer;
use Tuleap\GraphOnTrackersV5\XML\XMLPieChart;
use Tuleap\Project\Registration\Template\IssuesTemplateDashboardDefinition;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Report\Renderer\XML\XMLRenderer;
use Tuleap\Tracker\Template\IssuesTemplate;
use Tuleap\Widget\XML\XMLPreference;
use Tuleap\Widget\XML\XMLPreferenceValue;
use Tuleap\Widget\XML\XMLWidget;

/**
 * @psalm-immutable
 */
final class CompleteIssuesTemplate
{
    private const string ALL_ISSUES_PRIORITY_CHART_RENDERER_ID = 'All_Issues_Priority_Chart_to_be_used_in_remaining_XML_dashboard_definition';
    private const string OPEN_ISSUES_CHART_RENDERER_ID         = 'Open_Issues_Charts_to_be_used_in_remaining_XML_dashboard_definition';
    private const string ALL_ISSUES_CHART_RENDERER_ID          = 'All_Issues_Charts_to_be_used_in_remaining_XML_dashboard_definition';

    /**
     * @return XMLRenderer[]
     */
    public static function getAllIssuesRenderers(): array
    {
        return [
            (new XMLGraphOnTrackerRenderer('All Issues Charts'))
                ->withId(self::ALL_ISSUES_CHART_RENDERER_ID)
                ->withDescription('All Issues Charts')
                ->withCharts(
                    (new XMLBarChart(600, 400, 0, 'Priority'))
                        ->withDescription('Number of Artifacts by severity level')
                        ->withBase(new XMLReferenceByName(IssuesTemplate::PRIORITY_FIELD_NAME))
                        ->withGroup(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME)),
                    (new XMLPieChart(600, 400, 1, 'Status'))
                        ->withBase(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME)),
                    (new XMLPieChart(600, 400, 2, 'Assignment'))
                        ->withDescription('Number of Artifacts by Assignee')
                        ->withBase(new XMLReferenceByName(IssuesTemplate::ASSIGNED_TO_FIELD_NAME))
                ),
            (new XMLGraphOnTrackerRenderer('All Issues Priority Chart'))
                ->withId(self::ALL_ISSUES_PRIORITY_CHART_RENDERER_ID)
                ->withDescription('All Issues Priority Chart')
                ->withCharts(
                    (new XMLBarChart(600, 400, 0, 'Priority'))
                        ->withDescription('Number of Artifacts by severity level')
                        ->withBase(new XMLReferenceByName(IssuesTemplate::PRIORITY_FIELD_NAME))
                        ->withGroup(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME))
                ),
        ];
    }

    public static function getMyIssuesRenderer(): XMLRenderer
    {
        return (new XMLGraphOnTrackerRenderer('My Charts'))
            ->withDescription('My Charts')
            ->withCharts(
                (new XMLPieChart(600, 400, 0, 'Status'))
                    ->withBase(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME)),
                (new XMLPieChart(600, 400, 0, 'Assignment'))
                    ->withDescription('Number of Artifacts by Assignee')
                    ->withBase(new XMLReferenceByName(IssuesTemplate::ASSIGNED_TO_FIELD_NAME)),
                (new XMLBarChart(600, 400, 0, 'Severity'))
                    ->withDescription('Number of Artifacts by severity level')
                    ->withBase(new XMLReferenceByName(IssuesTemplate::PRIORITY_FIELD_NAME))
            );
    }

    public static function getOpenIssuesRenderer(): XMLRenderer
    {
        return (new XMLGraphOnTrackerRenderer('Open Issues Charts'))
            ->withId(self::OPEN_ISSUES_CHART_RENDERER_ID)
            ->withDescription('Open Issues Charts')
            ->withCharts(
                (new XMLBarChart(600, 400, 0, 'Priority'))
                    ->withDescription('Number of Artifacts by severity level')
                    ->withBase(new XMLReferenceByName(IssuesTemplate::PRIORITY_FIELD_NAME))
                    ->withGroup(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME)),
                (new XMLPieChart(600, 400, 1, 'Status'))
                    ->withBase(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME)),
                (new XMLPieChart(600, 400, 2, 'Assignment'))
                    ->withDescription('Number of Artifacts by Assignee')
                    ->withBase(new XMLReferenceByName(IssuesTemplate::ASSIGNED_TO_FIELD_NAME))
            );
    }

    public static function defineDashboards(IssuesTemplateDashboardDefinition $dashboard_definition): void
    {
        $dashboard_definition->withWidgetInLeftColumnOfGlobalDashboard(
            (new XMLWidget('plugin_tracker_projectrenderer'))
                ->withPreference((new XMLPreference('renderer'))
                    ->withValue(XMLPreferenceValue::ref('id', self::ALL_ISSUES_PRIORITY_CHART_RENDERER_ID))
                    ->withValue(XMLPreferenceValue::text('title', 'All issues Priority Chart')))
        );

        $dashboard_definition->withWidgetInRightColumnOfTeamDashboard(
            (new XMLWidget('plugin_tracker_projectrenderer'))
                ->withPreference((new XMLPreference('renderer'))
                    ->withValue(XMLPreferenceValue::ref('id', self::OPEN_ISSUES_CHART_RENDERER_ID))
                    ->withValue(XMLPreferenceValue::text('title', 'Open Issues Charts')))
        );

        $dashboard_definition->withWidgetInMainColumnOfManagerDashboard(
            (new XMLWidget('plugin_tracker_projectrenderer'))
                ->withPreference((new XMLPreference('renderer'))
                    ->withValue(XMLPreferenceValue::ref('id', self::ALL_ISSUES_CHART_RENDERER_ID))
                    ->withValue(XMLPreferenceValue::text('title', 'All issues Charts')))
        );
    }
}
