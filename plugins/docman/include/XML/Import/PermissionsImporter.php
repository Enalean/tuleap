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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Docman\XML\Import;

use Docman_Item;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Project\UGroupRetrieverWithLegacy;

class PermissionsImporter
{
    /**
     * @var \PermissionsManager
     */
    private $permission_manager;
    /**
     * @var UGroupRetrieverWithLegacy
     */
    private $ugroup_retriever_with_legacy;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        \PermissionsManager $permission_manager,
        UGroupRetrieverWithLegacy $ugroup_retriever_with_legacy,
        Project $project
    ) {
        $this->logger                       = $logger;
        $this->permission_manager           = $permission_manager;
        $this->ugroup_retriever_with_legacy = $ugroup_retriever_with_legacy;
        $this->project                      = $project;
    }

    public function importPermissions(Docman_Item $parent_item, Docman_Item $item, \SimpleXMLElement $node): void
    {
        if (empty($node->permissions)) {
            $this->clonePermissions($parent_item, $item);

            return;
        }

        foreach ($node->permissions->permission as $permission) {
            $ugroup_name = (string) $permission['ugroup'];
            $ugroup_id   = $this->ugroup_retriever_with_legacy->getUGroupId($this->project, $ugroup_name);
            if ($ugroup_id === null) {
                $this->logger->error(
                    "Custom ugroup '$ugroup_name' does not seem to exist for '{$this->project->getPublicName()}' project."
                );
                continue;
            }
            $this->permission_manager->addPermission((string) $permission['type'], $item->getId(), $ugroup_id);
        }
    }

    private function clonePermissions(Docman_Item $parent_item, Docman_Item $item): void
    {
        $this->permission_manager->clonePermissions(
            $parent_item->getId(),
            $item->getId(),
            ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE']
        );
    }
}
