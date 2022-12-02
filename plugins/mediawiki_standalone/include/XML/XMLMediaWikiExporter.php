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
use Tuleap\MediawikiStandalone\Permissions\ReadersRetriever;
use Tuleap\MediawikiStandalone\Permissions\WritersRetriever;
use Tuleap\Project\UGroupRetriever;

final class XMLMediaWikiExporter
{
    public function __construct(
        private LoggerInterface $logger,
        private ReadersRetriever $readers_retriever,
        private WritersRetriever $writers_retriever,
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
        $this->exportReadPermissions($project, $xml_content);
        $this->exportWritePermissions($project, $xml_content);
    }

    private function exportReadPermissions(\Project $project, \SimpleXMLElement $xml_content): void
    {
        $readers = $this->readers_retriever->getReadersUgroupIds($project);
        if (empty($readers)) {
            return;
        }

        $this->addUGroupChildren($project, $xml_content->addChild('read-access'), $readers);
    }

    private function exportWritePermissions(\Project $project, \SimpleXMLElement $xml_content): void
    {
        $writers = $this->writers_retriever->getWritersUgroupIds($project);
        if (empty($writers)) {
            return;
        }

        $this->addUGroupChildren($project, $xml_content->addChild('write-access'), $writers);
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
