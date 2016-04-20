<?php
/**
 * Copyright (c) Sogilis, 2016. All Rights Reserved.
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

namespace Tuleap\TeamforgeCompatTracker;

use Logger;
use Project;
use SimpleXMLElement;

class ReferencesImporter
{
    /** @var TeamforgeCompatDao */
    private $dao;

    /** @var Logger */
    private $logger;

    const TEAMFORGE_XREF_TRACKER  = 'tracker';
    const TEAMFORGE_XREF_ARTIFACT = 'artf';

    public function __construct(TeamforgeCompatDao $dao, Logger $logger)
    {
        $this->dao    = $dao;
        $this->logger = $logger;
    }

    public function importCompatRefXML(Project $project, SimpleXMLElement $xml, array $created_refs)
    {
        foreach ($xml->children() as $reference) {
            $source = (string) $reference['source'];
            $target = (string) $reference['target'];

            $target_on_system  = null;
            $reference_keyword = $this->getReferenceKeyword($source);

            if ($reference_keyword === self::TEAMFORGE_XREF_TRACKER) {
                $object_type = 'tracker';
            } elseif ($reference_keyword === self::TEAMFORGE_XREF_ARTIFACT) {
                $object_type = 'artifact';
            } else {
                $this->logger->warn("Cross reference kind '$reference_keyword' for $source not supported");
                continue;
            }

            if (isset($created_refs[$object_type][$target])) {
                $target_on_system = $created_refs[$object_type][$target];
            } else {
                $this->logger->warn("Could not find object for $source (wrong object type $object_type or missing imported object $target)");
                continue;
            }

            $row = $this->dao->getRef($source)->getRow();
            if (!empty($row)) {
                $this->logger->warn("The source $source already exists in the database. It will not be imported.");
                continue;
            }

            $this->dao->insertRef($project, $source, $target_on_system);
            $this->logger->info("Imported teamforge ref '$source' -> $object_type $target_on_system");
        }
    }

    private function getReferenceKeyword($reference)
    {
        $matches = array();
        if (preg_match('/^([a-zA-Z]*)/', $reference, $matches)) {
            return $matches[1];
        } else {
            return null;
        }
    }
}
