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

namespace Tuleap\ReferenceAliasGit;

use GitRepositoryFactory;
use ReferenceInstance;

class ReferencesBuilder
{

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var Dao
     */
    private $dao;

    public function __construct(
        Dao $dao,
        GitRepositoryFactory $repository_factory
    ) {
        $this->dao                = $dao;
        $this->repository_factory = $repository_factory;
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
     * @return Reference or null
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

        $repository = $this->repository_factory->getRepositoryById($row["repository_id"]);
        if (! $repository) {
            return null;
        }

        return new Reference($repository, $keyword, $row["sha1"]);
    }
}
