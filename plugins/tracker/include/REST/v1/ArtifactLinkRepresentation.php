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
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;

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
        return new self(array_merge(self::getForwardTypes($artifact), self::getReverseTypes($artifact)));
    }

    private static function getForwardTypes(Artifact $artifact)
    {
        $dao     = new TypeDao();
        $factory = self::getTypePresenterFactory();

        $types = [];

        foreach ($dao->searchForwardTypeShortNamesForGivenArtifact($artifact->getId()) as $type_row) {
            $type    = $factory->getFromShortname($type_row['shortname']);
            $types[] = self::formatType($type, $artifact, TypePresenter::FORWARD_LABEL);
        }

        return $types;
    }

    private static function getReverseTypes(Artifact $artifact)
    {
        $dao     = new TypeDao();
        $factory = self::getTypePresenterFactory();

        $types = [];

        foreach ($dao->searchReverseTypeShortNamesForGivenArtifact($artifact->getId()) as $type_row) {
            $type    = $factory->getFromShortname($type_row['shortname']);
            $types[] = self::formatType($type, $artifact, TypePresenter::REVERSE_LABEL);
        }

        return $types;
    }

    /**
     * @return TypePresenterFactory
     */
    private static function getTypePresenterFactory()
    {
        $type_dao                = new TypeDao();
        $artifact_link_usage_dao = new ArtifactLinksUsageDao();

        return new TypePresenterFactory($type_dao, $artifact_link_usage_dao);
    }

    private static function formatType(TypePresenter $type, Artifact $artifact, $direction)
    {
        $label = $direction === TypePresenter::FORWARD_LABEL ? $type->forward_label : $type->reverse_label;

        return [
            'shortname'    => $type->shortname,
            'direction'    => $direction,
            'label'        => $label,
            'uri'          => 'artifacts/' . $artifact->getId() . '/linked_artifacts?nature=' . urlencode($type->shortname) .
                              '&direction=' . urlencode($direction),
        ];
    }
}
