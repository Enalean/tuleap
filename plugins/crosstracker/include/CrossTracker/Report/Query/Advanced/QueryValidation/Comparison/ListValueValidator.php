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
use UserManager;

final class ListValueValidator
{
    /** @var EmptyStringChecker */
    private $empty_string_checker;

    /** @var UserManager */
    private $user_manager;

    public function __construct(EmptyStringChecker $empty_string_checker, UserManager $user_manager)
    {
        $this->empty_string_checker = $empty_string_checker;
        $this->user_manager         = $user_manager;
    }

    public function checkValueIsValid($value)
    {
        if ($this->empty_string_checker->isEmptyStringAProblem($value)) {
            throw new ListToEmptyStringException();
        } elseif ($value === '') {
            return;
        }

        $user = $this->user_manager->getUserByUserName($value);
        if ($user === null) {
            throw new NonExistentListValueException($value);
        }
    }
}
