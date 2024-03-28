<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\XML;

use Psr\Log\LoggerInterface;
use Tuleap\MediawikiStandalone\Permissions\ISaveProjectPermissions;
use Tuleap\Project\UGroupRetriever;

final class XMLMediaWikiImporter
{
    public function __construct(
        private LoggerInterface $logger,
        private UGroupRetriever $ugroup_retriever,
        private ISaveProjectPermissions $permissions_saver,
    ) {
    }

    public function import(\Project $project, \SimpleXMLElement $xml_input): void
    {
        $xml_mediawiki = $xml_input->{'mediawiki-standalone'};
        if (! $xml_mediawiki) {
            return;
        }

        $this->importPermissions($project, $xml_mediawiki);
    }

    private function importPermissions(\Project $project, \SimpleXMLElement $xml_mediawiki): void
    {
        $readers = $this->getReaders($project, $xml_mediawiki);
        $writers = $this->getWriters($project, $xml_mediawiki);
        $admins  = $this->getAdmins($project, $xml_mediawiki);

        if (count($readers) > 0 || count($writers) > 0) {
            $this->permissions_saver->saveProjectPermissions($project, $readers, $writers, $admins);
        }
    }

    /**
     * @return \ProjectUGroup[]
     */
    private function getReaders(\Project $project, \SimpleXMLElement $xml_mediawiki): array
    {
        $readers = [];

        if ($xml_mediawiki->{'read-access'}) {
            $this->logger->info("Importing read access rights for {$project->getUnixName()}");
            $readers = $this->getUgroupsForPermissions($project, $xml_mediawiki->{'read-access'});
        }

        $this->logFoundUGroups($project, $readers);

        return $readers;
    }

    /**
     * @return \ProjectUGroup[]
     */
    private function getWriters(\Project $project, \SimpleXMLElement $xml_mediawiki): array
    {
        $writers = [];

        if ($xml_mediawiki->{'write-access'}) {
            $this->logger->info("Importing write access rights for {$project->getUnixName()}");
            $writers = $this->getUgroupsForPermissions($project, $xml_mediawiki->{'write-access'});
        }

        $this->logFoundUGroups($project, $writers);

        return $writers;
    }

    /**
     * @return \ProjectUGroup[]
     */
    private function getAdmins(\Project $project, \SimpleXMLElement $xml_mediawiki): array
    {
        $admins = [];

        if ($xml_mediawiki->{'admin-access'}) {
            $this->logger->info("Importing admin access rights for {$project->getUnixName()}");
            $admins = $this->getUgroupsForPermissions($project, $xml_mediawiki->{'admin-access'});
        }

        $this->logFoundUGroups($project, $admins);

        return $admins;
    }

    private function logFoundUGroups(\Project $project, array $ugroups): void
    {
        if (count($ugroups) > 0) {
            $this->logger->info(
                'Found the following ugroups: ' .
                implode(', ', array_map(static fn(\ProjectUGroup $ugroup) => $ugroup->getNormalizedName(), $ugroups))
            );
        }
    }

    /**
     * @return \ProjectUGroup[]
     */
    private function getUgroupsForPermissions(\Project $project, \SimpleXMLElement $permission_xmlnode): array
    {
        $ugroups = [];
        foreach ($permission_xmlnode->ugroup as $ugroup) {
            $ugroup_name = (string) $ugroup;
            $ugroup      = $this->ugroup_retriever->getUGroupByName($project, $ugroup_name);
            if ($ugroup === null) {
                $this->logger->warning("Could not find any ugroup named $ugroup_name, skip it.");
                continue;
            }
            $ugroups[] = $ugroup;
        }

        return $ugroups;
    }
}
