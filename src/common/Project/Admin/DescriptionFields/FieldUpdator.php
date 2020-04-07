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

declare(strict_types=1);

namespace Tuleap\Project\Admin\DescriptionFields;

use Project;
use ProjectCreationData;
use Psr\Log\LoggerInterface;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO;
use Tuleap\Project\DescriptionFieldsFactory;

class FieldUpdator
{
    /**
     * @var DescriptionFieldsFactory
     */
    private $fields_factory;
    /**
     * @var ProjectDetailsDAO
     */
    private $dao;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        DescriptionFieldsFactory $fields_factory,
        ProjectDetailsDAO $dao,
        LoggerInterface $logger
    ) {
        $this->fields_factory = $fields_factory;
        $this->dao            = $dao;
        $this->logger         = $logger;
    }

    public function update(ProjectCreationData $data, int $group_id): void
    {
        $description_fields = $this->fields_factory->getAllDescriptionFields();

        foreach ($description_fields as $field) {
            $field_id    = $field["group_desc_id"];
            $desc_id_val = $data->getField($field_id);
            $this->storeFieldValue($group_id, $desc_id_val, (int) $field_id);
        }
    }

    public function updateFromArray(array $submitted_fields, Project $project): void
    {
        $description_fields = $this->fields_factory->getAllDescriptionFields();

        foreach ($description_fields as $field) {
            $field_id        = $field["group_desc_id"];
            if (isset($submitted_fields[$field_id])) {
                $submitted_value = $submitted_fields[$field_id];
                $this->storeFieldValue((int) $project->getID(), $submitted_value, (int) $field_id);
            }
        }
    }

    private function storeFieldValue(int $group_id, ?string $submitted_value, int $field_id): void
    {
        if ($submitted_value !== null && $submitted_value !== '') {
            $result = $this->dao->createGroupDescription($group_id, $field_id, $submitted_value);
            if (! $result) {
                $this->logger->debug(
                    sprintf("Impossible to create field %s with value %s", $field_id, $submitted_value)
                );
            }
        }
    }

    /**
     * @throws FieldDoesNotExistException
     * @throws MissingMandatoryFieldException
     */
    public function checkFieldConsistency(array $field_collection): void
    {
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
        foreach ($field_collection as $field_id => $field_value) {
            if (isset($mandatory_fields[$field_id])) {
                unset($mandatory_fields[$field_id]);
            } elseif (isset($optional_field[$field_id])) {
                unset($optional_field[$field_id]);
            } else {
                $non_existing_field[$field_id] = $field_id;
            }
        }

        if (count($mandatory_fields) !== 0) {
            throw new MissingMandatoryFieldException(
                sprintf(
                    'Mandatory field where missing: %s',
                    implode(
                        ', ',
                        array_values($mandatory_fields)
                    )
                )
            );
        }

        if (count($non_existing_field) !== 0) {
            throw new FieldDoesNotExistException(
                sprintf(
                    'Some fields does not exists: %s',
                    implode(
                        ', ',
                        array_values($non_existing_field)
                    )
                )
            );
        }
    }
}
