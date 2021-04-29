<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tracker_FormElement_Field_List;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MapStatusByValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoDuckTypedMatchingValueException;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;

final class StatusValueMapper implements MapStatusByValue
{
    /**
     * @var FieldValueMatcher
     */
    private $value_matcher;

    /**
     * @psalm-param Field<Tracker_FormElement_Field_List> $field_status_data
     * @throws NoDuckTypedMatchingValueException
     */
    public function mapStatusValueByDuckTyping(
        SourceChangesetValuesCollection $changeset_values_collection,
        Field $field_status_data
    ): MappedStatusValue {
        $matching_values = [];
        $field_status    = $field_status_data->getFullField();
        assert($field_status instanceof Tracker_FormElement_Field_List);
        foreach ($changeset_values_collection->getStatusValue()->getListValues() as $status_value) {
            $matching_value = $this->value_matcher->getMatchingBindValueByDuckTyping(
                $status_value,
                $field_status
            );
            if ($matching_value === null) {
                throw new NoDuckTypedMatchingValueException(
                    $status_value->getLabel(),
                    $field_status_data->getId(),
                    $field_status_data->getFullField()->getTrackerId()
                );
            }
            $matching_values[] = (int) $matching_value->getId();
        }

        return new MappedStatusValue($matching_values);
    }

    public function __construct(FieldValueMatcher $value_matcher)
    {
        $this->value_matcher = $value_matcher;
    }
}
