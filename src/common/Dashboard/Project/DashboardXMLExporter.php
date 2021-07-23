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

namespace Tuleap\Dashboard\Project;

use Psr\Log\LoggerInterface;
use Tuleap\Dashboard\Dashboard;

class DashboardXMLExporter
{
    private IRetrieveDashboards $retriever;
    private LoggerInterface $logger;

    public function __construct(IRetrieveDashboards $retriever, LoggerInterface $logger)
    {
        $this->retriever = $retriever;
        $this->logger    = $logger;
    }

    public function exportDashboards(\Project $project, \SimpleXMLElement $xml_element): void
    {
        $dashboards = $this->retriever->getAllProjectDashboards($project);
        if (empty($dashboards)) {
            $this->logger->debug("The project does not have any dashboards to export");
            return;
        }

        $this->logger->debug(sprintf("Found %d dashboards to export", count($dashboards)));
        $dashboards_element = $xml_element->addChild('dashboards');
        foreach ($dashboards as $dashboard) {
            $this->exportOneDashboard($dashboard, $dashboards_element);
        }
    }

    private function exportOneDashboard(Dashboard $dashboard, \SimpleXMLElement $dashboards_element): void
    {
        $this->logger->debug("Exporting dashboard " . $dashboard->getName());
        $dashboard_element = $dashboards_element->addChild('dashboard');
        $dashboard_element->addAttribute('name', $dashboard->getName());
    }
}
