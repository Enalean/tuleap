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


namespace Tuleap\ReferenceAliasMediawiki;

use Project;
use Reference;
use ReferenceInstance;
use ProjectManager;

class ReferencesBuilder
{

    /**
     * @var CompatibilityDao
     */
    private $dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(CompatibilityDao $dao, ProjectManager $project_manager)
    {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

    /**
     * Get a reference given a project, keyword and value (number after '#')
     *
     * @return Reference or null
     */
    public function getReference(Project $project, $keyword, $value)
    {
        return $this->findReference($project, $keyword, $keyword . $value);
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
                        (?P<key>' . ReferencesImporter::XREF_WIKI . ')
                        (?P<val>[0-9]+)
                    )
                    (?![_A-Za-z0-9])   # ensure the pattern is not followed by digits or letters
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
        $project         = $this->project_manager->getProject($project_id);
        $ref             = $match['ref'];
        $keyword         = $match['key'];
        $value           = $match['val'];

        $reference = $this->findReference($project, $keyword, $ref);

        if (empty($reference)) {
            return null;
        }

        $ref_instance = new ReferenceInstance($match[0], $reference, $ref);
        $ref_instance->computeGotoLink($keyword, $value, $reference->getGroupId());
        return $ref_instance;
    }

    /**
     * Find a reference given a keyword and the original complete reference
     *
     * @param $project Project
     * @param $keyword string reference keyword (wiki)
     * @param $reference string full reference (wiki76532)
     * @return Reference or null
     */
    private function findReference(Project $project, $keyword, $reference)
    {
        $row = $this->dao->getRef($reference)->getRow();
        if (empty($row)) {
            return null;
        }

        $target     = $row["target"];
        $project_id = $row["project_id"];

        switch ($keyword) {
            case 'wiki':
                $base_id     = 0;
                $description = '';
                $url         = "plugins/mediawiki/wiki/" . urlencode($project->getUnixNameLowerCase()) . "/index.php/" . urlencode($target);
                $visibility  = 'P';
                $service     = 'mediawiki';
                $nature      = 'mediawiki';
                $is_used     = 1;
                break;
            default:
                return null;
        }

        return new Reference(
            $base_id,
            $keyword,
            $description,
            $url,
            $visibility,
            $service,
            $nature,
            $is_used,
            $project_id
        );
    }
}
