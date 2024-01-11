<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison;

use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringChecker;
use Tuleap\User\RetrieveUserByUserName;

final class ListValueValidator
{
    public function __construct(private readonly EmptyStringChecker $empty_string_checker, private readonly RetrieveUserByUserName $retrieve_user_by_user_name)
    {
    }

    public function checkValueIsValid($value): void
    {
        if ($this->empty_string_checker->isEmptyStringAProblem($value)) {
            throw new ListToEmptyStringException();
        } elseif ($value === '') {
            return;
        }

        $user = $this->retrieve_user_by_user_name->getUserByUserName($value);
        if ($user === null) {
            throw new NonExistentListValueException($value);
        }
    }
}
