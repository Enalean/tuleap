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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use Tracker_FormElementFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

enum DuckTypedFieldType
{
    case NUMERIC;
    case TEXT;
    case DATE;
    case DATETIME;
    case STATIC_LIST;
    case UGROUP_LIST;

    /**
     * @return Ok<self>|Err<Fault>
     */
    public static function fromString(string $type_name): Ok | Err
    {
        return match ($type_name) {
            Tracker_FormElementFactory::FIELD_INTEGER_TYPE,
            Tracker_FormElementFactory::FIELD_FLOAT_TYPE      => Result::ok(self::NUMERIC),
            Tracker_FormElementFactory::FIELD_TEXT_TYPE,
            Tracker_FormElementFactory::FIELD_STRING_TYPE     => Result::ok(self::TEXT),
            Tracker_FormElementFactory::FIELD_DATE_TYPE       => Result::ok(self::DATE),
            FieldTypeRetrieverWrapper::FIELD_DATETIME_TYPE    => Result::ok(self::DATETIME),
            FieldTypeRetrieverWrapper::FIELD_STATIC_LIST_TYPE => Result::ok(self::STATIC_LIST),
            FieldTypeRetrieverWrapper::FIELD_UGROUP_LIST_TYPE => Result::ok(self::UGROUP_LIST),
            default                                           => Result::err(FieldTypeIsNotSupportedFault::build())
        };
    }
}
