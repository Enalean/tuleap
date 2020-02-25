<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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


namespace Tuleap\Docman\XML\Export;

class PermissionsExporter
{
    /**
     * @var array
     */
    private $project_ugroups_id_indexed_by_name;
    /**
     * @var PermissionsExporterDao
     */
    private $dao;

    /**
     * @param array<string, int> $project_ugroups_id_indexed_by_name
     */
    public function __construct(PermissionsExporterDao $dao, array $project_ugroups_id_indexed_by_name)
    {
        $this->dao                                = $dao;
        $this->project_ugroups_id_indexed_by_name = $project_ugroups_id_indexed_by_name;
    }

    public function exportPermissions(\SimpleXMLElement $node, \Docman_Item $item): void
    {
        $ugroups = $this->dao->searchPermissions((int) $item->getId());
        if (empty($ugroups)) {
            return;
        }

        $permissions = $node->addChild('permissions');
        foreach ($ugroups as $ugroup) {
            $ugroup_name = array_search((int) $ugroup['ugroup_id'], $this->project_ugroups_id_indexed_by_name, true);
            if (empty($ugroup_name)) {
                continue;
            }

            $permission = $permissions->addChild('permission');
            $permission->addAttribute('ugroup', $ugroup_name);
            $permission->addAttribute('type', $ugroup['permission_type']);
        }
    }
}
