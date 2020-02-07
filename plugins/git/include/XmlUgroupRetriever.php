<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git;

use GitXmlImporter;
use UGroupManager;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Project;

class XmlUgroupRetriever
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        LoggerInterface $logger,
        UGroupManager $ugroup_manager
    ) {
        $this->logger         = $logger;
        $this->ugroup_manager = $ugroup_manager;
    }

    /**
     * @return array
     */
    public function getUgroupIdsForPermissionNode(Project $project, SimpleXMLElement $permission_xmlnode)
    {
        $ugroup_ids = array();

        foreach ($permission_xmlnode->children() as $ugroup_xml) {
            if ($ugroup_xml->getName() === GitXmlImporter::UGROUP_TAG) {
                $ugroup = $this->getUgroup($project, $ugroup_xml);

                if (! $ugroup) {
                    continue;
                }

                $ugroup_id = $ugroup->getId();

                if ($ugroup && ! in_array($ugroup_id, $ugroup_ids)) {
                    array_push($ugroup_ids, $ugroup_id);
                }
            }
        }

        return $ugroup_ids;
    }

    /**
     * @return array
     */
    public function getUgroupsForPermissionNode(Project $project, SimpleXMLElement $permission_xmlnode)
    {
        $ugroups = array();

        foreach ($permission_xmlnode->children() as $ugroup_xml) {
            if ($ugroup_xml->getName() === GitXmlImporter::UGROUP_TAG) {
                $ugroup = $this->getUgroup($project, $ugroup_xml);

                if ($ugroup && ! in_array($ugroup, $ugroups)) {
                    array_push($ugroups, $ugroup);
                }
            }
        }

        return $ugroups;
    }

    /**
     * @return mixed Ugroup | null
     */
    private function getUgroup(Project $project, SimpleXMLElement $ugroup)
    {
        $ugroup_name = (string) $ugroup;
        $ugroup      = $this->ugroup_manager->getUGroupByName($project, $ugroup_name);

        if ($ugroup === null) {
            $this->logger->warning("Could not find any ugroup named $ugroup_name, skipping.");
        }

        return $ugroup;
    }
}
