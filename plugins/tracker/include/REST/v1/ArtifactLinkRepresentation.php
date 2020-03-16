<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Tracker_Artifact;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;

class ArtifactLinkRepresentation
{
    /**
     * @var array
     */
    public $natures;

    public function build(Tracker_Artifact $artifact)
    {
        $this->getForwardNatures($artifact);
        $this->getReverseNatures($artifact);
    }

    private function getForwardNatures(Tracker_Artifact $artifact)
    {
        $nature_dao     = new NatureDao();
        $nature_factory = $this->getNaturePresenterFactory();

        foreach ($nature_dao->searchForwardNatureShortNamesForGivenArtifact($artifact->getId()) as $nature_row) {
            $nature          = $nature_factory->getFromShortname($nature_row['shortname']);
            $this->natures[] = $this->formatNature($nature, $artifact, NaturePresenter::FORWARD_LABEL);
        }
    }

    private function getReverseNatures(Tracker_Artifact $artifact)
    {
        $nature_dao     = new NatureDao();
        $nature_factory = $this->getNaturePresenterFactory();

        foreach ($nature_dao->searchReverseNatureShortNamesForGivenArtifact($artifact->getId()) as $nature_row) {
            $nature          = $nature_factory->getFromShortname($nature_row['shortname']);
            $this->natures[] = $this->formatNature($nature, $artifact, NaturePresenter::REVERSE_LABEL);
        }
    }

    /**
     * @return NaturePresenterFactory
     */
    private function getNaturePresenterFactory()
    {
        $nature_dao              = new NatureDao();
        $artifact_link_usage_dao = new ArtifactLinksUsageDao();

        return new NaturePresenterFactory($nature_dao, $artifact_link_usage_dao);
    }

    private function formatNature(NaturePresenter $nature, Tracker_Artifact $artifact, $direction)
    {
        $label = $direction === NaturePresenter::FORWARD_LABEL ? $nature->forward_label : $nature->reverse_label;

        return array(
            "shortname"    => $nature->shortname,
            "direction"    => $direction,
            "label"        => $label,
            "uri"          => "artifacts/" . $artifact->getId() . "/linked_artifacts?nature=" . urlencode($nature->shortname) .
                              "&direction=" . urlencode($direction)
        );
    }
}
