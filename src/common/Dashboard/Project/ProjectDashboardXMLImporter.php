<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Dashboard\Project;

use PFUser;
use Project;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;

class ProjectDashboardXMLImporter
{
    /**
     * @var ProjectDashboardSaver
     */
    private $project_dashboard_saver;
    /**
     * @var \Logger
     */
    private $logger;

    public function __construct(ProjectDashboardSaver $project_dashboard_saver, \Logger $logger)
    {
        $this->project_dashboard_saver = $project_dashboard_saver;
        $this->logger                  = $logger;
    }

    public function import(\SimpleXMLElement $xml_element, PFUser $user, Project $project)
    {
        if ($xml_element->dashboards) {
            foreach ($xml_element->dashboards->dashboard as $dashboard) {
                try {
                    $this->project_dashboard_saver->save($user, $project, (string) $dashboard["name"]);
                } catch (UserCanNotUpdateProjectDashboardException $e) {
                    $this->logger->warn($e->getMessage());
                } catch (NameDashboardDoesNotExistException $e) {
                    $this->logger->warn($e->getMessage());
                } catch (NameDashboardAlreadyExistsException $e) {
                    $this->logger->warn($e->getMessage());
                }
            }
        }
    }
}
