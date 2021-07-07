<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\DescriptionFields;

use Tuleap\Project\REST\v1\ProjectPostRepresentation;

/**
 * @psalm-immutable
 */
final class ProjectRegistrationSubmittedFieldsCollection
{
    /**
     * @var ProjectRegistrationSubmittedField[] $collection
     */
    private $submitted_fields;

    private function __construct(array $submitted_fields)
    {
        $this->submitted_fields = $submitted_fields;
    }

    /**
     * @return ProjectRegistrationSubmittedField[]
     */
    public function getSubmittedFields(): array
    {
        return $this->submitted_fields;
    }

    public static function buildFromRESTProjectCreation(ProjectPostRepresentation $project_post_representation): self
    {
        $submitted_fields = [];

        if ($project_post_representation->fields !== null) {
            foreach ($project_post_representation->fields as $field_representation) {
                $submitted_fields[] = new ProjectRegistrationSubmittedField(
                    $field_representation->field_id,
                    $field_representation->value
                );
            }
        }

        return new ProjectRegistrationSubmittedFieldsCollection($submitted_fields);
    }

    /**
     * @psalm-param array<int, string> $submitted_fields_data
     */
    public static function buildFromArray(array $submitted_fields_data): self
    {
        $submitted_fields = [];
        foreach ($submitted_fields_data as $id => $value) {
            $submitted_fields[] = new ProjectRegistrationSubmittedField(
                $id,
                $value
            );
        }

        return new ProjectRegistrationSubmittedFieldsCollection(
            $submitted_fields
        );
    }
}
