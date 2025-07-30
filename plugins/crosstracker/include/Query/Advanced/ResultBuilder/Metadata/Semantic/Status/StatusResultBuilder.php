<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Status;

use LogicException;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatus;

final readonly class StatusResultBuilder implements BuildResultStatus
{
    public function __construct(private RetrieveArtifact $retrieve_artifact, private RetrieveSemanticStatus $status_semantic_retriever)
    {
    }

    #[\Override]
    public function getResult(array $select_results, PFUser $user): SelectedValuesCollection
    {
        $values = [];
        $alias  = '@status';

        foreach ($select_results as $result) {
            $id = $result['id'];
            if (! isset($values[$id])) {
                $values[$id] = [];
            }

            $label = $result[$alias];
            $color = $result[$alias . '_color'];

            if (! $this->isAValidAllowedStatusValue($label, $id, $user)) {
                continue;
            }

            $values[$id] = $this->buildValue($label, $color);
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@status', CrossTrackerSelectedType::TYPE_STATIC_LIST),
            array_map(static fn(array $selected_values) => new SelectedValue('@status', new StaticListRepresentation($selected_values)), $values),
        );
    }

    private function isAValidAllowedStatusValue(string|array|null $label, int $id, PFUser $user): bool
    {
        if ($label === null) {
            return false;
        }

        $artifact = $this->retrieve_artifact->getArtifactById($id);
        if ($artifact === null) {
            throw new LogicException("Artifact #$id not found");
        }

        $semantic = $this->status_semantic_retriever->fromTracker($artifact->getTracker());
        $field    = $semantic->getField();
        if ($field === null || ! $field->userCanRead($user)) {
            return false;
        }

        return true;
    }

    private function buildValue(string|array $label, string|array|null $color): array
    {
        if (! is_array($label) && ! is_array($color)) {
            return [new StaticListValueRepresentation($label, $color)];
        }

        $i = 0;
        return array_map(
            static function (string $one_label) use (&$i, $color) {
                return new StaticListValueRepresentation(
                    $one_label,
                    is_array($color) ? ($color[$i++] ?? null) : $color,
                );
            },
            array_filter($label),
        );
    }
}
