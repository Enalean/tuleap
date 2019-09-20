<?php
/**
 * Copyright Enalean (c) 2016 - 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_Value_ArtifactLinkDao;

class NatureIsChildLinkRetriever
{

    /**
     * @var Tracker_FormElement_Field_Value_ArtifactLinkDao
     */
    private $artifact_link_dao;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $factory;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElement_Field_Value_ArtifactLinkDao $artifact_link_dao
    ) {
        $this->factory           = $artifact_factory;
        $this->artifact_link_dao = $artifact_link_dao;
    }

    /** @return ParentOfArtifactCollection */
    public function getParentsHierarchy(Tracker_Artifact $artifact)
    {
        $collection = new ParentOfArtifactCollection();
        $this->addParentsOfArtifactToCollection($artifact, $collection, array());

        return $collection;
    }

    private function addParentsOfArtifactToCollection(
        Tracker_Artifact $artifact,
        ParentOfArtifactCollection $collection,
        array $already_seen_artifacts
    ) {
        if (isset($already_seen_artifacts[$artifact->getId()])) {
            return;
        }
        $already_seen_artifacts[$artifact->getId()] = 1;

        $parents = $this->getDirectParents($artifact);

        if (count($parents) > 0) {
            $collection->addArtifacts($parents);
            if (count($parents) > 1) {
                $collection->setIsGraph(true);
            } else {
                $this->addParentsOfArtifactToCollection($parents[0], $collection, $already_seen_artifacts);
            }
        }
    }

    /** @return Tracker_Artifact[] */
    public function getChildren(Tracker_Artifact $artifact)
    {
        return $this->factory->getIsChildLinkedArtifactsById($artifact);
    }

    /**
     * @return Tracker_Artifact[]
     */
    public function getDirectParents(Tracker_Artifact $artifact)
    {
        $parents = array();
        foreach ($this->artifact_link_dao->searchIsChildReverseLinksById($artifact->getId()) as $row) {
            $parents[] = $this->factory->getArtifactById($row['artifact_id']);
        }
        return $parents;
    }
}
