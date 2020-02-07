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

namespace Tuleap\ReferenceAliasTracker;

use Psr\Log\LoggerInterface;
use Project;
use SimpleXMLElement;
use Tuleap\Project\XML\Import\ImportConfig;

class ReferencesImporter
{
    /** @var Dao */
    private $dao;

    /** @var LoggerInterface */
    private $logger;

    public const XREF_TRACKER  = 'tracker';
    public const XREF_ARTF     = 'artf';
    public const XREF_PLAN     = 'plan';

    public function __construct(Dao $dao, LoggerInterface $logger)
    {
        $this->dao    = $dao;
        $this->logger = $logger;
    }

    public function importCompatRefXML(ImportConfig $configuration, Project $project, SimpleXMLElement $xml, array $created_refs)
    {
        if ($xml->count() === 0) {
            return;
        }

        foreach ($xml->children() as $reference) {
            $source = (string) $reference['source'];
            $target = (string) $reference['target'];

            $target_on_system  = null;
            $reference_keyword = $this->getReferenceKeyword($source);

            if ($reference_keyword === self::XREF_TRACKER) {
                $object_type = 'tracker';
            } elseif ($reference_keyword === self::XREF_ARTF) {
                $object_type = 'artifact';
            } elseif ($reference_keyword === self::XREF_PLAN) {
                $object_type = 'artifact';
            } else {
                $this->logger->warning("Cross reference kind '$reference_keyword' for $source not supported");
                continue;
            }

            if (isset($created_refs[$object_type][$target])) {
                $target_on_system = $created_refs[$object_type][$target];
            } else {
                $this->logger->warning("Could not find object for $source (wrong object type $object_type or missing imported object $target)");
                continue;
            }

            if (! $configuration->isForce('references')) {
                $row = $this->dao->getRef($source)->getRow();
                if (!empty($row)) {
                    $this->logger->warning("The source $source already exists in the database. It will not be imported.");
                    continue;
                }
            }

            if (! $this->dao->insertRef($project, $source, $target_on_system)) {
                $this->logger->error("Could not insert object for $source");
            } else {
                $this->logger->info("Imported original ref '$source' -> $object_type $target_on_system");
            }
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
