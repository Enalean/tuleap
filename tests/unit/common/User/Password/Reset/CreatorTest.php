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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Test\Builders\UserTestBuilder;

final class CreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var SplitTokenVerificationStringHasher&MockObject
     */
    private $hasher;
    /**
     * @var LostPasswordDAO&MockObject
     */
    private $dao;
    /**
     * @var Creator
     */
    private $token_creator;

    protected function setUp(): void
    {
        $this->hasher = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->dao    = $this->createMock(\Tuleap\User\Password\Reset\LostPasswordDAO::class);

        $this->token_creator = new Creator($this->dao, $this->hasher);
    }

    public function testItCreatesToken(): void
    {
        $this->hasher->method('computeHash')->willReturn('random_hashed');

        $user = UserTestBuilder::aUser()->withId(101)->build();

        $this->dao->method('create')->with(101, 'random_hashed', self::anything())->willReturn(22);

        $token = $this->token_creator->create($user);
        self::assertEquals(22, $token->getID());
    }

    public function testHandlesTokenCreationRejection(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();

        $this->hasher->method('computeHash')->willReturn('random_hashed');
        $this->dao->method('create')->willThrowException(new RecentlyCreatedCodeException());

        $this->expectException(RecentlyCreatedCodeException::class);

        $this->token_creator->create($user);
    }
}
