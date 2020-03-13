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

use Tuleap\ReferenceAliasTracker\Reference\ArtifactReference;
use Tuleap\ReferenceAliasTracker\Reference\TrackerReference;
use Reference;
use ReferenceInstance;

class OriginalReferencesBuilder
{

    /**
     * @var Dao
     */
    private $dao;

    public function __construct(Dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Get a reference given a project, keyword and value (number after '#')
     *
     * @return Reference or null
     */
    public function getReference($keyword, $value)
    {
        return $this->findReference($keyword, $keyword . $value);
    }

    /**
     * For the return format, see ReferenceManager::additional_references
     *
     * @return array a list of extra reference spec.
     */
    public function getExtraReferenceSpecs()
    {
        return array(
            array(
                'cb'     => array($this, 'referenceFromMatch'),
                'regexp' => '/
                    (?<![_a-zA-Z0-9])  # ensure the pattern is not following digits or letters
                    (?P<ref>
                        (?P<key>' . ReferencesImporter::XREF_ARTF . '
                            |' . ReferencesImporter::XREF_TRACKER . '
                            |' . ReferencesImporter::XREF_PLAN . ')
                        (?P<val>[0-9]+)
                    )
                    (?![_A-Za-z0-9])   # ensure the pattern is not folloed by digits or letters
                /x'
            )
        );
    }

    /**
     * Callback for when references are matched in a text
     * @return ReferenceInstance or null
     */
    public function referenceFromMatch($match, $project_id)
    {
        $ref             = $match['ref'];
        $keyword         = $match['key'];
        $value           = $match['val'];

        $reference = $this->findReference($keyword, $ref);

        if (empty($reference)) {
            return null;
        }

        $ref_instance = new ReferenceInstance($match[0], $reference, $ref);
        $ref_instance->computeGotoLink($keyword, $value, $project_id);
        return $ref_instance;
    }

    /**
     * Find a reference given a keyword and the original complete reference
     *
     * @param $project Project
     * @param $keyword string reference keyword (pkg)
     * @param $reference string full reference (pkg1232)
     * @return Reference or null
     */
    private function findReference($keyword, $reference)
    {
        $row = $this->dao->getRef($reference)->getRow();
        if (empty($row)) {
            return null;
        }

        $target     = $row["target"];
        $project_id = $row["project_id"];

        switch ($keyword) {
            case ReferencesImporter::XREF_TRACKER:
                $reference = new TrackerReference($keyword, $target, $project_id);
                break;
            case ReferencesImporter::XREF_PLAN:
            case ReferencesImporter::XREF_ARTF:
                $reference = new ArtifactReference($keyword, $target, $project_id);
                break;
            default:
                $reference = null;
        }

        return $reference;
    }
}
