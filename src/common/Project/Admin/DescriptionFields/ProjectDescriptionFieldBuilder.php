<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Project\Admin\DescriptionFields;

use Project;
use Tuleap\Project\DescriptionFieldsFactory;

class ProjectDescriptionFieldBuilder
{
    /**
     * @var DescriptionFieldsFactory
     */
    private $fields_factory;

    public function __construct(DescriptionFieldsFactory $fields_factory)
    {
        $this->fields_factory = $fields_factory;
    }

    public function build(Project $project)
    {
        $all_custom_fields = $this->fields_factory->getAllDescriptionFields();

        return $this->getCustomFieldsPresenter($project, $all_custom_fields);
    }

    private function getCustomFieldsPresenter(Project $project, $all_custom_fields)
    {
        $project_custom_fields = $project->getProjectsDescFieldsValue();

        $presenters = array();
        foreach ($all_custom_fields as $custom_field) {
            $field_value = $this->getFieldValue($project_custom_fields, $custom_field);

            $presenters[] = array(
                'label'       => DescriptionFieldLabelBuilder::getFieldTranslatedName($custom_field['desc_name']),
                'is_empty'    => $field_value == '',
                'value'       => $field_value ? $field_value : $GLOBALS['Language']->getText('global', 'none'),
                'is_required' => $custom_field['desc_required']
            );
        }

        return $presenters;
    }

    private function getFieldValue(array $project_custom_fields, array $custom_field)
    {
        foreach ($project_custom_fields as $project_field) {
            if ($project_field['group_desc_id'] == $custom_field['group_desc_id']) {
                return $project_field['value'];
            }
        }

        return '';
    }
}
