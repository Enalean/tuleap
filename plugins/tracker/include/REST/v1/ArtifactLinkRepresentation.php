<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;

/**
 * @psalm-immutable
 */
class ArtifactLinkRepresentation
{
    /**
     * @var array
     */
    public $natures;

    private function __construct(array $natures)
    {
        $this->natures = $natures;
    }

    public static function build(Artifact $artifact): self
    {
        return new self(array_merge(self::getForwardNatures($artifact), self::getReverseNatures($artifact)));
    }

    private static function getForwardNatures(Artifact $artifact)
    {
        $nature_dao     = new NatureDao();
        $nature_factory = self::getNaturePresenterFactory();

        $natures = [];

        foreach ($nature_dao->searchForwardNatureShortNamesForGivenArtifact($artifact->getId()) as $nature_row) {
            $nature    = $nature_factory->getFromShortname($nature_row['shortname']);
            $natures[] = self::formatNature($nature, $artifact, NaturePresenter::FORWARD_LABEL);
        }

        return $natures;
    }

    private static function getReverseNatures(Artifact $artifact)
    {
        $nature_dao     = new NatureDao();
        $nature_factory = self::getNaturePresenterFactory();

        $natures = [];

        foreach ($nature_dao->searchReverseNatureShortNamesForGivenArtifact($artifact->getId()) as $nature_row) {
            $nature    = $nature_factory->getFromShortname($nature_row['shortname']);
            $natures[] = self::formatNature($nature, $artifact, NaturePresenter::REVERSE_LABEL);
        }

        return $natures;
    }

    /**
     * @return NaturePresenterFactory
     */
    private static function getNaturePresenterFactory()
    {
        $nature_dao              = new NatureDao();
        $artifact_link_usage_dao = new ArtifactLinksUsageDao();

        return new NaturePresenterFactory($nature_dao, $artifact_link_usage_dao);
    }

    private static function formatNature(NaturePresenter $nature, Artifact $artifact, $direction)
    {
        $label = $direction === NaturePresenter::FORWARD_LABEL ? $nature->forward_label : $nature->reverse_label;

        return [
            "shortname"    => $nature->shortname,
            "direction"    => $direction,
            "label"        => $label,
            "uri"          => "artifacts/" . $artifact->getId() . "/linked_artifacts?nature=" . urlencode($nature->shortname) .
                              "&direction=" . urlencode($direction)
        ];
    }
}
