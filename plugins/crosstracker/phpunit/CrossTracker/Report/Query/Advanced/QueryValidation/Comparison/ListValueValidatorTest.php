<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringChecker;

class ListValueValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var EmptyStringChecker */
    private $empty_string_checker;

    /** @var \UserManager */
    private $user_manager;

    /** @var ListValueValidator */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empty_string_checker = Mockery::mock(EmptyStringChecker::class);

        $this->user_manager = Mockery::mock(\UserManager::class);

        $this->validator = new ListValueValidator(
            $this->empty_string_checker,
            $this->user_manager
        );
    }

    public function testEmptyStringIsValidWhenAllowed()
    {
        $this->empty_string_checker->shouldReceive('isEmptyStringAProblem')->withArgs([''])->andReturn(false);

        $this->validator->checkValueIsValid('');
    }

    public function testEmptyStringIsInvalidWhenForbidden()
    {
        $this->empty_string_checker->shouldReceive('isEmptyStringAProblem')->withArgs([''])->andReturn(true);

        $this->expectException(ListToEmptyStringException::class);
        $this->validator->checkValueIsValid('');
    }

    public function testExistingUserIsValid()
    {
        $this->empty_string_checker->shouldReceive('isEmptyStringAProblem')->withArgs(['lromo'])->andReturn(false);
        $user = Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserByUserName', 'lromo')->andReturn($user);

        $this->validator->checkValueIsValid('lromo');
    }

    public function testNonExistentUserIsInvalid()
    {
        $this->empty_string_checker->shouldReceive('isEmptyStringAProblem')->withArgs(['cillovsky'])->andReturn(false);
        $this->user_manager->shouldReceive('getUserByUserName', 'cillovsky')->andReturn(null);

        $this->expectException(NonExistentListValueException::class);
        $this->validator->checkValueIsValid('cillovsky');
    }
}
