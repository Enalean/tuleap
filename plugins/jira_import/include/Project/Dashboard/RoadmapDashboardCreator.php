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

use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Roadmap\RoadmapProjectWidget;

class RoadmapDashboardCreator
{
    public function createRoadmapDashboard(
        SimpleXMLElement $project_xml,
        SimpleXMLElement $xml_dashboards,
        array $jira_issue_types,
        string $jira_epic_issue_type,
        LoggerInterface $logger,
    ): void {
        foreach ($jira_issue_types as $issue_type) {
            if ($issue_type->getName() === $jira_epic_issue_type) {
                $epic_xml_id = $issue_type->getId();
                $logger->info("Epic tracker found. Its XML id is: $epic_xml_id");

                $found_semantic = $project_xml->xpath('/project/trackers/tracker[@id="' . $epic_xml_id . '"]/semantics/semantic[@type="timeframe"]');
                $logger->info("Timeframe semantic found for Epic tracker.");
                if (is_array($found_semantic) && count($found_semantic) > 0) {
                    $xml_dashboard_roadmap = $xml_dashboards->addChild("dashboard");
                    $xml_dashboard_roadmap->addAttribute('name', 'Roadmap');

                    $xml_dashboard_roadmap_column = $xml_dashboard_roadmap->addChild("line")->addChild("column");

                    $xml_roadmap_widget = $xml_dashboard_roadmap_column->addChild("widget");
                    $xml_roadmap_widget->addAttribute("name", RoadmapProjectWidget::ID);

                    $xml_roadmap_widget_preference = $xml_roadmap_widget->addChild("preference");
                    $xml_roadmap_widget_preference->addAttribute("name", "roadmap");

                    $xml_roadmap_widget_preference
                        ->addChild("value", "Roadmap")
                        ->addAttribute("name", "title");

                    $xml_roadmap_widget_preference
                        ->addChild("value", $epic_xml_id)
                        ->addAttribute("name", "tracker_id");
                }
            }
        }
    }
}
