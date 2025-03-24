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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\Date;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use PFUser;
use Tracker_FormElement_Field_Date;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\DateResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Query\Advanced\SelectResultKey;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;

final readonly class DateResultBuilder
{
    public function __construct(
        private RetrieveArtifact $retrieve_artifact,
        private RetrieveUsedFields $retrieve_used_fields,
    ) {
    }

    public function getResult(DuckTypedFieldSelect $field, array $select_results, PFUser $user): SelectedValuesCollection
    {
        $values = [];
        $alias  = SelectResultKey::fromDuckTypedField($field);

        foreach ($select_results as $result) {
            $id = (int) $result['id'];
            if (isset($values[$id])) {
                continue;
            }
            $value = $result[(string) $alias];
            if ($value === null) {
                $values[$id] = new SelectedValue($field->name, new DateResultRepresentation($value, false));
                continue;
            }

            if (is_int($value)) {
                $value = (new DateTimeImmutable("@$value"))
                    ->setTimezone(new DateTimeZone(TimezoneRetriever::getUserTimezone($user)))
                    ->format(DateTimeImmutable::ATOM);
            }
            $artifact = $this->retrieve_artifact->getArtifactById($id);
            if ($artifact === null) {
                throw new LogicException("Artifact #$id not found");
            }
            $tracker_field = $this->retrieve_used_fields->getUsedFieldByName($artifact->getTrackerId(), $field->name);
            if (! ($tracker_field instanceof Tracker_FormElement_Field_Date)) {
                throw new LogicException("Field $field->name not found in tracker {$artifact->getTrackerId()}");
            }
            $values[$id] = new SelectedValue($field->name, new DateResultRepresentation($value, $tracker_field->isTimeDisplayed()));
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($field->name, CrossTrackerSelectedType::TYPE_DATE),
            $values,
        );
    }
}
