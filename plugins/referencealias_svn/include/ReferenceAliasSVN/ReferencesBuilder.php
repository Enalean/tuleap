<?php
/**
 * Copyright (c) Enalean SAS, 2016. All Rights Reserved.
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

namespace Tuleap\ReferenceAliasSVN;

use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Reference\Reference;
use ReferenceInstance;
use ProjectManager;
use Project_NotFoundException;

class ReferencesBuilder
{

    /**
     * @var Tuleap\SVN\Repository\RepositoryManager
     */
    private $repository_manager;

    /**
     * @var Dao
     */
    private $dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        Dao $dao,
        ProjectManager $project_manager,
        RepositoryManager $repository_manager
    ) {
        $this->dao                = $dao;
        $this->project_manager    = $project_manager;
        $this->repository_manager = $repository_manager;
    }

    /**
     * Get a reference given a project, keyword and value (number after '#')
     *
     * @return Reference|null
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
                        (?P<key>' . ReferencesImporter::XREF_CMMT . ')
                        (?P<val>[0-9]+)
                    )
                    (?![_A-Za-z0-9])   # ensure the pattern is not folloed by digits or letters
                /x'
            )
        );
    }

    /**
     * Callback for when references are matched in a text
     * @return ReferenceInstance|null
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
     * @return Reference|null
     */
    private function findReference($keyword, $reference)
    {
        if ($keyword !== ReferencesImporter::XREF_CMMT) {
            return null;
        }

        $row = $this->dao->getRef($reference)->getRow();
        if (empty($row)) {
            return null;
        }

        try {
            $project    = $this->project_manager->getValidProject($row["project_id"]);
            $repository = $this->repository_manager->getByIdAndProject($row["repository_id"], $project);

            return new Reference($project, $repository, $keyword, $row["revision_id"]);
        } catch (Project_NotFoundException $exception) {
            return null;
        }
    }
}
