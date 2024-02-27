<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1\Project;

use Event;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\REST\ResourcesInjector;
use Tuleap\REST\v1\ProjectFieldsMinimalRepresentation;

final readonly class ProjectRepresentationBuilder
{
    public function __construct(private \EventManager $event_manager, private DescriptionFieldsFactory $description_fields_factory)
    {
    }

    public function build(\Project $project, \PFUser $user): ProjectRepresentation
    {
        $resources = [];
        $this->event_manager->processEvent(
            Event::REST_PROJECT_RESOURCES,
            [
                'version' => 'v1',
                'project' => $project,
                'resources' => &$resources,
            ]
        );

        $resources_injector = new ResourcesInjector();
        $resources_injector->declareProjectResources($resources, $project);

        $informations = [];
        $this->event_manager->processEvent(
            Event::REST_PROJECT_ADDITIONAL_INFORMATIONS,
            [
                'project' => $project,
                'current_user' => $user,
                'informations' => &$informations,
            ]
        );

        $project_field_representations = $this->getAdditionalFields($project);

        return ProjectRepresentation::build(
            $project,
            $user,
            $resources,
            $informations,
            $project_field_representations
        );
    }

    /**
     * @return ProjectFieldsMinimalRepresentation[]
     */
    private function getAdditionalFields(\Project $project): array
    {
        $description_fields_infos = $this->description_fields_factory->getAllDescriptionFields();
        $fields_values            = $project->getProjectsDescFieldsValue();

        $values = [];
        foreach ($description_fields_infos as $description_fields_info) {
            $values[$description_fields_info["desc_name"]] = $this->getFieldValue(
                $fields_values,
                $description_fields_info
            );
        }

        $project_field_representations = [];
        foreach ($values as $key => $value) {
            $project_field_representations[] = new ProjectFieldsMinimalRepresentation($key, $value);
        }

        return $project_field_representations;
    }

    private function getFieldValue(array $project_custom_fields, array $custom_field): string
    {
        foreach ($project_custom_fields as $project_field) {
            if ($project_field['group_desc_id'] == $custom_field['group_desc_id']) {
                return $project_field['value'];
            }
        }

        return '';
    }
}
