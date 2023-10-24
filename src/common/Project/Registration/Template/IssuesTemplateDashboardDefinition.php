<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Dashboard\XML\XMLColumn;
use Tuleap\Dashboard\XML\XMLDashboard;
use Tuleap\Dashboard\XML\XMLLine;
use Tuleap\Event\Dispatchable;
use Tuleap\Widget\XML\XMLPreference;
use Tuleap\Widget\XML\XMLPreferenceValue;
use Tuleap\Widget\XML\XMLWidget;

final class IssuesTemplateDashboardDefinition implements Dispatchable
{
    public const NAME = 'issuesTemplateDashboardDefinition';

    private XMLColumn $global_dashboard_left_column;
    private XMLColumn $team_dashboard_left_column;
    private XMLColumn $team_dashboard_right_column;
    private XMLColumn $manager_dashboard_main_column;

    public function __construct(private EventDispatcherInterface $dispatcher)
    {
        $this->global_dashboard_left_column  = new XMLColumn();
        $this->team_dashboard_left_column    = new XMLColumn();
        $this->team_dashboard_right_column   = new XMLColumn();
        $this->manager_dashboard_main_column = new XMLColumn();
    }

    public function getDashboards(): array
    {
        $this->dispatcher->dispatch($this);

        $global_dashboard = (new XMLDashboard('0 - Global Dashboard'))
            ->withLine(
                XMLLine::withLayout('two-columns-big-small')
                    ->withColumn(
                        $this->global_dashboard_left_column
                            ->withWidget(new XMLWidget('projectheartbeat'))
                    )
                    ->withColumn((new XMLColumn())
                        ->withWidget((new XMLWidget('projectnote'))
                            ->withPreference((new XMLPreference('note'))
                                ->withValue(
                                    XMLPreferenceValue::text(
                                        'title',
                                        'Note from the Tuleap team'
                                    )
                                )
                                ->withValue(
                                    XMLPreferenceValue::text(
                                        'content',
                                        <<<EOS
                                        Welcome to your new project!

                                        It is based on Issue Tracking template.

                                        You will find a tracker named "Issues" to trace and track all your items
                                        and a Git to create all repositories your team needs.

                                        EOS
                                    )
                                )))
                        ->withWidget(new XMLWidget('projectmembers'))
                        ->withWidget(new XMLWidget('projectcontacts')))
            );

        $team_dashboard = (new XMLDashboard('0 - Team View'))
            ->withLine(
                XMLLine::withDefaultLayout()
                    ->withColumn($this->team_dashboard_left_column)
                    ->withColumn($this->team_dashboard_right_column)
            );

        $manager_dashboard = (new XMLDashboard('2 - Manager View'))
            ->withLine(
                XMLLine::withDefaultLayout()
                    ->withColumn($this->manager_dashboard_main_column)
            );

        return [$global_dashboard, $team_dashboard, $manager_dashboard];
    }

    public function withWidgetInLeftColumnOfGlobalDashboard(XMLWidget $widget): void
    {
        $this->global_dashboard_left_column = $this->global_dashboard_left_column->withWidget($widget);
    }

    public function withWidgetInLeftColumnOfTeamDashboard(XMLWidget $widget): void
    {
        $this->team_dashboard_left_column = $this->team_dashboard_left_column->withWidget($widget);
    }

    public function withWidgetInRightColumnOfTeamDashboard(XMLWidget $widget): void
    {
        $this->team_dashboard_right_column = $this->team_dashboard_right_column->withWidget($widget);
    }

    public function withWidgetInMainColumnOfManagerDashboard(XMLWidget $widget): void
    {
        $this->manager_dashboard_main_column = $this->manager_dashboard_main_column->withWidget($widget);
    }
}
