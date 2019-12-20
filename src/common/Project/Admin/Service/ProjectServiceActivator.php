<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Project\Admin\Service;

use EventManager;
use Project;
use ProjectCreationData;
use Tuleap\Project\Event\ProjectRegistrationActivateService;
use Tuleap\Service\ServiceCreator;

class ProjectServiceActivator
{
    /**
     * @var ServiceCreator
     */
    private $service_creator;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var \ServiceDao
     */
    private $service_dao;

    public function __construct(ServiceCreator $service_creator, EventManager $event_manager, \ServiceDao $service_dao)
    {
        $this->service_creator = $service_creator;
        $this->event_manager   = $event_manager;
        $this->service_dao     = $service_dao;
    }

    /**
     * Activate the same services on $group_id than those activated on $template_group
     * protected for testing purpose
     */
    public function activateServicesFromTemplate(
        Project $group,
        Project $template_group,
        ProjectCreationData $data,
        array $legacy
    ): void {
        $group_id           = (int) $group->getID();
        $template_id        = (int) $template_group->getID();
        $is_template_system = $template_group->isSystem();
        $template_name      = $is_template_system ? '' : $template_group->getUnixName();

        $template_service_list = $this->service_dao->getServiceInfoQueryForNewProject($legacy, $template_id);

        foreach ($template_service_list as $template_service) {
            $service_info = $data->getServiceInfo($template_service['service_id']);
            if (isset($service_info['is_used'])) {
                $is_used = $service_info['is_used'];
            } else {
                $is_used = $template_service['is_used'];
                if ($template_service['short_name'] === 'admin' || $template_service['short_name'] === 'summary') {
                    $is_used = '1';
                }
                if ($template_service['short_name'] === 'tracker' || $template_service['short_name'] === 'svn') {
                    $is_used = '0';
                }
            }

            $this->service_creator->createService(
                $template_service,
                $group_id,
                [
                    'system'  => $is_template_system,
                    'name'    => $template_name,
                    'id'      => $template_id,
                    'is_used' => $is_used,
                ]
            );
        }

        $event = new ProjectRegistrationActivateService($group, $template_group, $legacy);
        $this->event_manager->processEvent($event);
    }
}
