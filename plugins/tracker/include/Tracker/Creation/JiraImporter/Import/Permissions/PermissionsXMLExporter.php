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
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class PermissionsXMLExporter
{
    public function exportFieldsPermissions(SimpleXMLElement $node_tracker, FieldMappingCollection $field_mapping_collection): void
    {
        $permissions_node = $node_tracker->addChild('permissions');

        foreach ($field_mapping_collection->getAllMappings() as $mapping) {
            $permission_node  = $permissions_node->addChild('permission');
            $permission_node->addAttribute("scope", "field");
            $permission_node->addAttribute("REF", $mapping->getXMLId());
            $permission_node->addAttribute("ugroup", "UGROUP_PROJECT_MEMBERS");
            $permission_node->addAttribute("type", "PLUGIN_TRACKER_FIELD_READ");
        }
    }
}
