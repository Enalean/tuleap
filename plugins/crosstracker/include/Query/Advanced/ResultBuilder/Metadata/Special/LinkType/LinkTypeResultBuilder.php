<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\LinkType;

use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\ArtifactLinkTypeRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\RetrieveSystemTypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\REST\v1\TrackerFieldRepresentations\LinkTypeRepresentation;

final readonly class LinkTypeResultBuilder
{
    public function __construct(public RetrieveSystemTypePresenter $type_presenter_factory)
    {
    }

    public function getResult(array $select_results, Option $direction): SelectedValuesCollection
    {
        $values = [];

        foreach ($select_results as $result) {
            $id          = $result['id'];
            $values[$id] = $direction->match(
                fn(string $link_direction) => $this->createSelectedValueWithLinkType($id, $result, $link_direction),
                fn() => $this->createEmptySelectedValue()
            );
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@link_type', CrossTrackerSelectedType::LINK_TYPE),
            $values,
        );
    }

    private function createSelectedValueWithLinkType(int $id, array $result, string $link_direction): SelectedValue
    {
        $shortname = $result['@link_type'] ?? '';
        if ($shortname === '') {
            return $this->createEmptySelectedValue();
        }
        $uri = 'artifacts/' . urlencode((string) $id) . '/linked_artifacts?nature=' . urlencode($shortname) .
            '&direction=' . urlencode($link_direction);

        $label = $result[$link_direction];
        if ($label !== '') {
            return $this->buildRepresentationWithLabel($shortname, $link_direction, $label, $uri);
        }

        $system_type_presenter = $this->type_presenter_factory->getSystemTypeFromShortname($shortname);

        $label = ($link_direction === LinkDirection::FORWARD->value) ? $system_type_presenter?->forward_label : $system_type_presenter?->reverse_label;
        return $this->buildRepresentationWithLabel($shortname, $link_direction, $label, $uri);
    }

    private function createEmptySelectedValue(): SelectedValue
    {
        return $this->buildRepresentationWithLabel('', '', '', '');
    }

    private function buildRepresentationWithLabel(string $shortname, string $link_direction, mixed $label, string $uri): SelectedValue
    {
        $link_type_representation = new LinkTypeRepresentation($shortname, $link_direction, $label, $uri);
        return new SelectedValue('@link_type', ArtifactLinkTypeRepresentation::build($link_type_representation));
    }
}
