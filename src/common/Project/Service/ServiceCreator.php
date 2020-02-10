<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use Feedback;
use ForgeConfig;
use Project;
use ProjectManager;
use ServiceDao;

class ServiceCreator
{
    /**
     * @var ServiceDao
     */
    private $dao;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(ServiceDao $dao, ProjectManager $project_manager)
    {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

    /**
     * @throws UnableToCreateServiceException
     */
    public function createService(Project $project, ServicePOSTData $service_data)
    {
        $result = $this->dao->create(
            $project->getID(),
            $service_data->getLabel(),
            $service_data->getIconName(),
            $service_data->getDescription(),
            $service_data->getShortName(),
            $service_data->getLink(),
            $service_data->isActive(),
            $service_data->isUsed(),
            $service_data->getScope(),
            $service_data->getRank(),
            $service_data->isInNewTab()
        );
        if (! $result) {
            throw new UnableToCreateServiceException();
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText('project_admin_servicebar', 's_create_success')
        );
        $this->forceRegenerationOfCacheInProjectManager($project);

        if ($service_data->isActive() && (int) $project->getID() === 100) {
            $this->addServiceToAllProjects($service_data);
        }
    }

    /**
     * @param string $link
     * @return string
     */
    private function replaceProtocolAndDomainInLink($link)
    {
        $sys_default_protocol = 'http';
        if (ForgeConfig::get('sys_https_host')) {
            $sys_default_protocol = 'https';
        }
        $link = str_replace('$sys_default_domain', $GLOBALS['sys_default_domain'], $link);
        $link = str_replace('$sys_default_protocol', $sys_default_protocol, $link);

        return $link;
    }

    private function forceRegenerationOfCacheInProjectManager(Project $project)
    {
        $this->project_manager->clear($project->getID());
        $this->project_manager->getProject($project->getID());
    }

    private function addServiceToAllProjects(ServicePOSTData $service_data)
    {
        $link   = $service_data->getLink();
        $link   = $this->replaceProtocolAndDomainInLink($link);
        $nbproj = 1;

        $sql     = "SELECT group_id FROM groups WHERE group_id!=100";
        $result1 = db_query($sql);
        while ($arr = db_fetch_array($result1)) {
            $my_group_id = $arr['group_id'];
            // Substitute values in links
            $my_link = $link;
            if (strstr($link, '$projectname')) {
                // Don't check project name if not needed.
                // When it is done here, the service bar will not appear updated on the current page
                $my_link = str_replace('$projectname', $this->project_manager->getProject($my_group_id)->getUnixName(), $my_link);
            }
            $my_link = str_replace('$group_id', $my_group_id, $my_link);

            $result = $this->dao->create(
                $my_group_id,
                $service_data->getLabel(),
                $service_data->getIconName(),
                $service_data->getDescription(),
                $service_data->getShortName(),
                $my_link,
                $service_data->isActive(),
                $service_data->isUsed(),
                $service_data->getScope(),
                $service_data->getRank(),
                $service_data->isInNewTab()
            );
            if ($result) {
                $nbproj++;
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('project_admin_servicebar', 'cant_create_s_for_p', $my_group_id)
                );
            }
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText('project_admin_servicebar', 's_add_success', $nbproj)
        );
    }
}
