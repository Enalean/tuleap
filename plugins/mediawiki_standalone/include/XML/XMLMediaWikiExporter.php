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
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissions;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\Project\UGroupRetriever;

final class XMLMediaWikiExporter
{
    public function __construct(
        private LoggerInterface $logger,
        private ProjectPermissionsRetriever $permissions_retriever,
        private UGroupRetriever $ugroup_retriever,
    ) {
    }

    public function exportToXml(
        \Project $project,
        \SimpleXMLElement $xml_content,
    ): void {
        if ($project) {
            $this->logger->info('Export MediaWiki standalone');
        }
        $root_node = $xml_content->addChild('mediawiki-standalone');
        $this->exportMediawikiPermissions($project, $root_node);
    }

    private function exportMediawikiPermissions(\Project $project, \SimpleXMLElement $xml_content): void
    {
        $project_permissions = $this->permissions_retriever->getProjectPermissions($project);

        $this->exportReadPermissions($project, $xml_content, $project_permissions);
        $this->exportWritePermissions($project, $xml_content, $project_permissions);
        $this->exportAdminPermissions($project, $xml_content, $project_permissions);
    }

    private function exportReadPermissions(\Project $project, \SimpleXMLElement $xml_content, ProjectPermissions $project_permissions): void
    {
        if (empty($project_permissions->readers)) {
            return;
        }

        $this->addUGroupChildren($project, $xml_content->addChild('read-access'), $project_permissions->readers);
    }

    private function exportWritePermissions(\Project $project, \SimpleXMLElement $xml_content, ProjectPermissions $project_permissions): void
    {
        if (empty($project_permissions->writers)) {
            return;
        }

        $this->addUGroupChildren($project, $xml_content->addChild('write-access'), $project_permissions->writers);
    }

    private function exportAdminPermissions(\Project $project, \SimpleXMLElement $xml_content, ProjectPermissions $project_permissions): void
    {
        if (empty($project_permissions->admins)) {
            return;
        }

        $this->addUGroupChildren($project, $xml_content->addChild('admin-access'), $project_permissions->admins);
    }

    private function addUGroupChildren(\Project $project, \SimpleXMLElement $xml_content, array $ugroup_ids): void
    {
        $cdata = new \XML_SimpleXMLCDATAFactory();
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup = $this->ugroup_retriever->getUGroup($project, $ugroup_id);
            if ($ugroup) {
                $cdata->insert($xml_content, 'ugroup', $ugroup->getNormalizedName());
            }
        }
    }
}
