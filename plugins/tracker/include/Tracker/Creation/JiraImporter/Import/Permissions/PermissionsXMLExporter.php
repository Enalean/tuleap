<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Permissions;

use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\FormElement\Field\XML\ReadPermission;
use Tuleap\Tracker\FormElement\Field\XML\SubmitPermission;
use Tuleap\Tracker\FormElement\Field\XML\UpdatePermission;
use Tuleap\Tracker\FormElement\Field\XML\XMLFieldPermission;

class PermissionsXMLExporter
{
    /**
     * @var array
     */
    private $read_only_field_ids = [
        AlwaysThereFieldsExporter::JIRA_ARTIFACT_ID_FIELD_ID,
        AlwaysThereFieldsExporter::JIRA_LINK_FIELD_ID,
    ];

    public function exportFieldsPermissions(SimpleXMLElement $node_tracker, FieldMappingCollection $field_mapping_collection): void
    {
        $permissions_node = $node_tracker->addChild('permissions');

        foreach ($field_mapping_collection->getAllMappings() as $mapping) {
            $this->exportReadPermission($permissions_node, $mapping);

            if (in_array($mapping->getJiraFieldId(), $this->read_only_field_ids)) {
                continue;
            }

            $this->exportSubmitPermission($permissions_node, $mapping);
            $this->exportUpdatePermission($permissions_node, $mapping);
        }
    }

    private function exportReadPermission(SimpleXMLElement $permissions_node, FieldMapping $mapping): void
    {
        (new XMLFieldPermission($mapping->getXMLId(), new ReadPermission('UGROUP_ANONYMOUS')))->export($permissions_node);
    }

    private function exportSubmitPermission(SimpleXMLElement $permissions_node, FieldMapping $mapping): void
    {
        (new XMLFieldPermission($mapping->getXMLId(), new SubmitPermission('UGROUP_REGISTERED')))->export($permissions_node);
    }

    private function exportUpdatePermission(SimpleXMLElement $permissions_node, FieldMapping $mapping): void
    {
        (new XMLFieldPermission($mapping->getXMLId(), new UpdatePermission('UGROUP_PROJECT_MEMBERS')))->export($permissions_node);
    }
}
