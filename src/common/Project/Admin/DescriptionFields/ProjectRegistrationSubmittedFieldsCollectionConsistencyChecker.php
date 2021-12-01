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

use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\ProjectRegistrationErrorsCollection;

class ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker
{
    private DescriptionFieldsFactory $fields_factory;

    public function __construct(DescriptionFieldsFactory $fields_factory)
    {
        $this->fields_factory = $fields_factory;
    }

    public function checkFieldConsistency(
        ProjectRegistrationSubmittedFieldsCollection $field_collection,
        ProjectRegistrationErrorsCollection $errors_collection,
    ): void {
        $mandatory_fields = [];
        $optional_field   = [];
        foreach ($this->fields_factory->getAllDescriptionFields() as $field) {
            $field_id = $field['group_desc_id'];
            if ($field['desc_required']) {
                $mandatory_fields[$field_id] = $field['desc_name'];
            } else {
                $optional_field[$field_id] = $field['desc_name'];
            }
        }

        $non_existing_field = [];
        foreach ($field_collection->getSubmittedFields() as $submitted_field) {
            $field_id = $submitted_field->getFieldId();

            if (isset($mandatory_fields[$field_id])) {
                unset($mandatory_fields[$field_id]);
            } elseif (isset($optional_field[$field_id])) {
                unset($optional_field[$field_id]);
            } else {
                $non_existing_field[$field_id] = $field_id;
            }
        }

        if (count($mandatory_fields) !== 0) {
            $errors_collection->addError(
                new MissingMandatoryFieldException($mandatory_fields)
            );
        }

        if (count($non_existing_field) !== 0) {
            $errors_collection->addError(
                new FieldDoesNotExistException($non_existing_field)
            );
        }
    }
}
