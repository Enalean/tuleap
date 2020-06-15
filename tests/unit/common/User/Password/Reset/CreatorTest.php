<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\User\Password\Reset;

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Test\Builders\UserTestBuilder;

final class CreatorTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LostPasswordDAO
     */
    private $dao;
    /**
     * @var Creator
     */
    private $token_creator;

    protected function setUp(): void
    {
        $this->hasher = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $this->dao    = \Mockery::spy(\Tuleap\User\Password\Reset\LostPasswordDAO::class);

        $this->token_creator = new Creator($this->dao, $this->hasher);
    }

    public function testItCreatesToken(): void
    {
        $this->hasher->shouldReceive('computeHash')->andReturns('random_hashed');

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(101);

        $this->dao->shouldReceive('create')->with(101, 'random_hashed', \Mockery::any())->andReturns(22);

        $token = $this->token_creator->create($user);
        $this->assertEquals(22, $token->getID());
    }

    public function testHandlesTokenCreationRejection(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();

        $this->hasher->shouldReceive('computeHash')->andReturns('random_hashed');
        $this->dao->shouldReceive('create')->andReturns(null);

        $this->assertNull($this->token_creator->create($user));
    }
}
