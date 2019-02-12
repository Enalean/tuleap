<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\Transition\TransitionCreationParameters;

class TransitionValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionValidator */
    private $validator;

    private const FROM_ID = 516;
    private const TO_ID = 137;

    protected function setUp(): void
    {
        $this->validator = new TransitionValidator();
    }

    public function testValidateForCreationReturnsValidatedParameters()
    {
        $workflow = Mockery::mock(\Workflow::class);
        $workflow
            ->shouldReceive('getTransition')
            ->with(self::FROM_ID, self::TO_ID)
            ->andReturnNull();
        $workflow
            ->shouldReceive('getAllFieldValues')
            ->andReturn([self::FROM_ID => 'Todo', self::TO_ID => 'On Going']);

        $result = $this->validator->validateForCreation($workflow, self::FROM_ID, self::TO_ID);

        $expected = new TransitionCreationParameters(self::FROM_ID, self::TO_ID);
        $this->assertEquals($expected, $result);
    }

    public function testValidateForCreationAcceptsFromIdZero()
    {
        $workflow = Mockery::mock(\Workflow::class);
        $workflow
            ->shouldReceive('getTransition')
            ->with(null, self::TO_ID)
            ->andReturnNull();
        $workflow
            ->shouldReceive('getAllFieldValues')
            ->andReturn([self::TO_ID => 'On Going']);

        $result = $this->validator->validateForCreation($workflow, 0, self::TO_ID);

        $expected = new TransitionCreationParameters(null, self::TO_ID);
        $this->assertEquals($expected, $result);
    }

    public function testValidateForCreationThrowsWhenIdenticalToAndFromIds()
    {
        $workflow = Mockery::mock(\Workflow::class);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->validator->validateForCreation($workflow, self::FROM_ID, self::FROM_ID);
    }

    public function testValidateForCreationThrowsWhenTransitionAlreadyExists()
    {
        $workflow = Mockery::mock(\Workflow::class);
        $workflow
            ->shouldReceive('getTransition')
            ->with(self::FROM_ID, self::TO_ID)
            ->andReturn(Mockery::mock(\Transition::class));

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->validator->validateForCreation($workflow, self::FROM_ID, self::TO_ID);
    }

    public function testValidateForCreationThrowsWhenFromIdDoesNotExistInFieldValues()
    {
        $workflow = Mockery::mock(\Workflow::class);
        $workflow
            ->shouldReceive('getTransition')
            ->with(self::FROM_ID, self::TO_ID)
            ->andReturnNull();
        $workflow
            ->shouldReceive('getAllFieldValues')
            ->andReturn([self::TO_ID => 'On Going']);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->validator->validateForCreation($workflow, self::FROM_ID, self::TO_ID);
    }

    public function testValidateForCreationThrowsWhenToIdDoesNotExistInFieldValues()
    {
        $workflow = Mockery::mock(\Workflow::class);
        $workflow
            ->shouldReceive('getTransition')
            ->with(self::FROM_ID, self::TO_ID)
            ->andReturnNull();
        $workflow
            ->shouldReceive('getAllFieldValues')
            ->andReturn([self::FROM_ID => 'Todo']);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->validator->validateForCreation($workflow, self::FROM_ID, self::TO_ID);
    }
}
