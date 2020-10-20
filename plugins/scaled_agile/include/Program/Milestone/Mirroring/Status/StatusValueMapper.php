<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Milestone\Mirroring\Status;

use Tuleap\ScaledAgile\Program\Milestone\Mirroring\CopiedValues;
use Tuleap\ScaledAgile\Program\Milestone\SynchronizedFields;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;

class StatusValueMapper
{
    /**
     * @var FieldValueMatcher
     */
    private $value_matcher;

    public function __construct(FieldValueMatcher $value_matcher)
    {
        $this->value_matcher = $value_matcher;
    }

    /**
     * @throws NoDuckTypedMatchingValueException
     */
    public function mapStatusValueByDuckTyping(
        CopiedValues $copied_values,
        SynchronizedFields $target_fields
    ): MappedStatusValue {
        $matching_values     = [];
        $target_status_field = $target_fields->getStatusField();
        foreach ($copied_values->getStatusValue()->getListValues() as $status_value) {
            $matching_value = $this->value_matcher->getMatchingBindValueByDuckTyping(
                $status_value,
                $target_status_field
            );
            if ($matching_value === null) {
                throw new NoDuckTypedMatchingValueException(
                    $status_value->getLabel(),
                    (int) $target_status_field->getId()
                );
            }
            $matching_values[] = (int) $matching_value->getId();
        }
        return new MappedStatusValue($matching_values);
    }
}
