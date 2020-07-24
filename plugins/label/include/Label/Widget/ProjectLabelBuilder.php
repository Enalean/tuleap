<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label\Widget;

class ProjectLabelBuilder
{

    public function build(array $project_labels, array $config_label)
    {
        $labels = [];

        foreach ($project_labels as $key => $project_label) {
            $labels[$key]             = $project_label;
            $labels[$key]['selected'] = in_array($project_label['id'], $config_label);
        }

        return $labels;
    }

    public function buildSelectedLabels(array $project_labels, array $config_labels)
    {
        $selected_labels = [];

        $formatter     = new ProjectLabelConfigurationLabelsFormatter();
        $config_labels = $formatter->getLabelsIds($config_labels);
        foreach ($project_labels as $project_label) {
            if (in_array($project_label['id'], $config_labels)) {
                $project_label['is_outline'] = $project_label['is_outline'] === '1' ? 'true' : 'false';
                $selected_labels[]           = $project_label;
            }
        }

        return $selected_labels;
    }
}
