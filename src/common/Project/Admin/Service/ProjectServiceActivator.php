<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\Service;

use EventManager;
use Project;
use ReferenceManager;
use Tuleap\Project\Event\ProjectRegistrationActivateService;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\Service\ServiceLinkDataBuilder;
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
    /**
     * @var \ServiceManager
     */
    private $service_manager;
    /**
     * @var ServiceLinkDataBuilder
     */
    private $link_data_builder;

    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    public function __construct(
        ServiceCreator $service_creator,
        EventManager $event_manager,
        \ServiceDao $service_dao,
        \ServiceManager $service_manager,
        ServiceLinkDataBuilder $link_data_builder,
        ReferenceManager $reference_manager,
    ) {
        $this->service_creator   = $service_creator;
        $this->event_manager     = $event_manager;
        $this->service_dao       = $service_dao;
        $this->service_manager   = $service_manager;
        $this->link_data_builder = $link_data_builder;
        $this->reference_manager = $reference_manager;
    }

    /**
     * Activate the same services on $group_id than those activated on $template_group
     * protected for testing purpose
     */
    public function activateServicesFromTemplate(
        Project $group,
        Project $template_group,
        ProjectCreationData $data,
        array $legacy,
    ): void {
        $template_id = (int) $template_group->getID();

        if ($data->isIsBuiltFromXml()) {
            $this->inheritServicesFromXml($data, $group);
        } else {
            $template_service_list = $this->service_dao->getServiceInfoQueryForNewProject($legacy, $template_id);
            $this->inheritServicesFromDefaultProject($data, $template_group, $group, $template_service_list);
        }

        $event = new ProjectRegistrationActivateService($group, $template_group, $legacy);
        $this->event_manager->processEvent($event);
    }

    private function inheritServicesFromDefaultProject(
        ProjectCreationData $data,
        Project $template_group,
        Project $group,
        $template_service_list,
    ): void {
        $group_id    = (int) $group->getID();
        $template_id = (int) $template_group->getID();

        foreach ($template_service_list as $template_service) {
            $is_used = $this->retrieveLegacyServiceUsage($data, $template_service, $template_service['short_name']);

            $this->service_creator->createService(
                $template_service,
                $group_id,
                [
                    'system'  => $template_group->isSystem(),
                    'name'    => $this->getTemplateName($template_group),
                    'id'      => $template_id,
                    'is_used' => $is_used,
                ]
            );
        }
    }

    private function retrieveLegacyServiceUsage(
        ProjectCreationData $data,
        array $template_service,
        string $short_name,
    ) {
        $service_info = $data->getServiceInfo((int) $template_service['service_id']);
        if (isset($service_info['is_used'])) {
            return $service_info['is_used'];
        }

        if ($short_name === 'admin' || $short_name === 'summary') {
            return '1';
        }
        if ($short_name === 'tracker' || $short_name === 'svn') {
            return '0';
        }

        return $template_service['is_used'];
    }

    private function inheritServicesFromXml(ProjectCreationData $data, Project $project): void
    {
        $data_services = $data->getDataServices();
        if (! $data_services) {
            return;
        }

        foreach ($data_services as $id => $is_used) {
            $service_info = $data->getServiceInfo((int) $id);
            if (! isset($service_info)) {
                continue;
            }

            $service = $this->service_manager->getService($id);

            $short_name = $service->getShortName();

            $icon = "";
            if ($short_name !== "summary") {
                $icon = $service->getIcon();
            }

            if ($short_name === 'admin' || $short_name === 'summary') {
                $is_used = true;
            } else {
                $is_used = $is_used['is_used'];
            }

            $created_project_id = (int) $project->getID();

            $this->service_dao->create(
                $created_project_id,
                $service->getLabel(),
                $icon,
                $service->getDescription(),
                $service->getShortName(),
                $this->link_data_builder->substituteVariablesInLink($project, $service->getUrl()),
                true,
                $is_used,
                $service->getScope(),
                $service->getRank(),
                $service->isOpenedInNewTab()
            );

            if ($short_name !== "") {
                $this->reference_manager->addSystemReferencesForService(
                    (int) $data->getBuiltFromTemplateProject()->getProject()->getID(),
                    $created_project_id,
                    $short_name
                );

                $this->reference_manager->updateReferenceForService(
                    $created_project_id,
                    $short_name,
                    (int) $is_used
                );
            }
        }
    }

    private function getTemplateName(Project $template_group)
    {
        $is_template_system = $template_group->isSystem();

        return $is_template_system ? '' : $template_group->getUnixName();
    }
}
