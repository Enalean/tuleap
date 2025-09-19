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

namespace Tuleap\Tracker\REST\v1\TrackerFieldRepresentations;

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\SystemTypePresenterBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;

/**
 * @psalm-immutable
 */
final class ArtifactLinkRepresentation
{
    /**
     * @param LinkTypeRepresentation[] $natures
     */
    private function __construct(public readonly array $natures)
    {
    }

    public static function build(Artifact $artifact): self
    {
        return new self(array_merge(self::getForwardTypes($artifact), self::getReverseTypes($artifact)));
    }

    /**
     * @return LinkTypeRepresentation[]
     */
    private static function getForwardTypes(Artifact $artifact): array
    {
        $dao     = new TypeDao();
        $factory = self::getTypePresenterFactory();

        $types = [];

        foreach ($dao->searchForwardTypeShortNamesForGivenArtifact($artifact->getId()) as $type_row) {
            $type = $factory->getFromShortname($type_row['shortname']);
            if (! $type) {
                break;
            }
            $types[] = self::formatType($type, $artifact, LinkDirection::FORWARD);
        }

        return $types;
    }

    /**
     * @return LinkTypeRepresentation[]
     */
    private static function getReverseTypes(Artifact $artifact): array
    {
        $dao     = new TypeDao();
        $factory = self::getTypePresenterFactory();

        $types = [];

        foreach ($dao->searchReverseTypeShortNamesForGivenArtifact($artifact->getId()) as $type_row) {
            $type = $factory->getFromShortname($type_row['shortname']);
            if (! $type) {
                break;
            }
            $types[] = self::formatType($type, $artifact, LinkDirection::REVERSE);
        }

        return $types;
    }

    private static function getTypePresenterFactory(): TypePresenterFactory
    {
        $type_dao                = new TypeDao();
        $artifact_link_usage_dao = new ArtifactLinksUsageDao();

        return new TypePresenterFactory($type_dao, $artifact_link_usage_dao, new SystemTypePresenterBuilder(\EventManager::instance()));
    }

    private static function formatType(TypePresenter $type, Artifact $artifact, LinkDirection $direction): LinkTypeRepresentation
    {
        $label = $direction === LinkDirection::FORWARD ? $type->forward_label : $type->reverse_label;
        $uri   = 'artifacts/' . $artifact->getId() . '/linked_artifacts?nature=' . urlencode($type->shortname) .
            '&direction=' . urlencode($direction->value);

        return new LinkTypeRepresentation($type->shortname, $direction->value, $label, $uri);
    }
}
