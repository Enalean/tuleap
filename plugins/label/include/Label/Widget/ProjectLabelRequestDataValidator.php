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

use Codendi_Request;
use Valid_UInt;

class ProjectLabelRequestDataValidator
{
    public function validateDataFromRequest(Codendi_Request $request, array $project_labels)
    {
        $selected_labels = $request->get('project-labels');
        if (! $selected_labels) {
            throw new ProjectLabelAreMandatoryException();
        }

        if (! $request->validArray(new Valid_UInt('project-labels'))) {
            throw new ProjectLabelAreNotValidException();
        }

        $labels_ids = $this->extractProjectLabelsIds($project_labels);
        foreach ($request->get('project-labels') as $label_id) {
            if (! in_array($label_id, $labels_ids)) {
                throw new ProjectLabelDoesNotBelongToProjectException();
            }
        }
    }

    private function extractProjectLabelsIds(array $project_labels)
    {
        $ids = [];

        foreach ($project_labels as $label) {
            $ids[] = $label['id'];
        }

        return $ids;
    }
}
