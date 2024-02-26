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

use Psr\Log\LoggerInterface;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\ProjectCreationData;

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
        LoggerInterface $logger,
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
            $desc_id_val = $data->getFieldValue($field_id);
            $this->storeFieldValue($group_id, $desc_id_val, $field_id);
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
}
