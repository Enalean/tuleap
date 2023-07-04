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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringChecker;

final class ListValueValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EmptyStringChecker&MockObject $empty_string_checker;
    private \UserManager&MockObject $user_manager;
    private ListValueValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empty_string_checker = $this->createMock(EmptyStringChecker::class);

        $this->user_manager = $this->createMock(\UserManager::class);

        $this->validator = new ListValueValidator(
            $this->empty_string_checker,
            $this->user_manager
        );
    }

    public function testEmptyStringIsValidWhenAllowed(): void
    {
        $this->empty_string_checker->method('isEmptyStringAProblem')->with('')->willReturn(false);

        $this->expectNotToPerformAssertions();
        $this->validator->checkValueIsValid('');
    }

    public function testEmptyStringIsInvalidWhenForbidden(): void
    {
        $this->empty_string_checker->method('isEmptyStringAProblem')->with('')->willReturn(true);

        $this->expectException(ListToEmptyStringException::class);
        $this->validator->checkValueIsValid('');
    }

    public function testExistingUserIsValid(): void
    {
        $this->empty_string_checker->method('isEmptyStringAProblem')->with('lromo')->willReturn(false);
        $user = UserTestBuilder::aUser()->build();
        $this->user_manager->method('getUserByUserName')->willReturn($user);

        $this->expectNotToPerformAssertions();
        $this->validator->checkValueIsValid('lromo');
    }

    public function testNonExistentUserIsInvalid(): void
    {
        $this->empty_string_checker->method('isEmptyStringAProblem')->with('cillovsky')->willReturn(false);
        $this->user_manager->method('getUserByUserName')->willReturn(null);

        $this->expectException(NonExistentListValueException::class);
        $this->validator->checkValueIsValid('cillovsky');
    }
}
