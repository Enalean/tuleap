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

namespace Tuleap\ArtifactsFolders\Folder;

use Codendi_Request;
use PFUser;
use Tracker_Artifact_View_View;
use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tracker_ArtifactFactory;
use TemplateRendererFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\ArtifactsFolders\Nature\NatureIsFolderPresenter;

class PresenterBuilder
{
    /**
     * @var NatureDao
     */
    private $dao;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(NatureDao $dao, Tracker_ArtifactFactory $artifact_factory)
    {
        $this->dao              = $dao;
        $this->artifact_factory = $artifact_factory;
    }

    /** @return Presenter */
    public function build(PFUser $user, Tracker_Artifact $artifact)
    {
        $linked_artifacts_ids = $this->dao->getReverseLinkedArtifactIds(
            $artifact->getId(),
            NatureIsFolderPresenter::NATURE_IS_FOLDER,
            PHP_INT_MAX,
            0
        );

        $artifact_representations = array();
        foreach ($linked_artifacts_ids as $artifact_id) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $artifact_id);
            if ($artifact) {
                $artifact_representations[] = $this->getArtifactRepresentation($user, $artifact);
            }
        }

        return new Presenter(
            $artifact_representations
        );
    }

    private function getArtifactRepresentation(PFUser $user, Tracker_Artifact $artifact)
    {
        $artifact_representation = new ArtifactPresenter();
        $artifact_representation->build($user, $artifact);

        return $artifact_representation;
    }
}
