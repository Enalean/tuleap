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
use GitXmlImporterUGroupNotFoundException;
use UGroupManager;
use Logger;
use SimpleXMLElement;
use Project;

class XmlUgroupRetriever
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        Logger $logger,
        UGroupManager $ugroup_manager
    ) {
        $this->logger         = $logger;
        $this->ugroup_manager = $ugroup_manager;
    }

    /**
     * @return array
     *
     * @throws GitXmlImporterUGroupNotFoundException
     */
    public function getUgroupIdsForPermissionNode(Project $project, SimpleXMLElement $permission_xmlnode)
    {
        $ugroup_ids = array();

        foreach ($permission_xmlnode->children() as $ugroup) {
            if ($ugroup->getName() === GitXmlImporter::UGROUP_TAG) {
                $ugroup_name = (string) $ugroup;
                $ugroup      = $this->ugroup_manager->getUGroupByName($project, $ugroup_name);

                if ($ugroup === null) {
                    $this->logger->error("Could not find any ugroup named $ugroup_name");
                    throw new GitXmlImporterUGroupNotFoundException($ugroup_name);
                }

                array_push($ugroup_ids, $ugroup->getId());
            }
        }

        return $ugroup_ids;
    }
}
