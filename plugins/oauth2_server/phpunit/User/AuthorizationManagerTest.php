<?php
/**
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

namespace Tuleap\OAuth2Server\User;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AuthorizationManager */
    private $manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao     = M::mock(AuthorizationDao::class);
        $this->manager = new AuthorizationManager($this->dao);
    }

    public function testSaveAuthorization(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $app_id = 65;
        $this->dao->shouldReceive('doesAuthorizationExist')
            ->once()
            ->with($user, $app_id)
            ->andReturnFalse();
        $this->dao->shouldReceive('create')
            ->once()
            ->with($user, $app_id);

        $this->manager->saveAuthorization($user, $app_id);
    }

    public function testSaveAuthorizationDoesNotSaveDuplicate(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $app_id = 65;
        $this->dao->shouldReceive('doesAuthorizationExist')
            ->once()
            ->with($user, $app_id)
            ->andReturnTrue();
        $this->dao->shouldNotReceive('create');

        $this->manager->saveAuthorization($user, $app_id);
    }
}
