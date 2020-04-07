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

namespace Tuleap\User\Password\Change;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\User\Password\Reset\Revoker;
use Tuleap\User\SessionManager;

class PasswordChangerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $user_manager;
    /**
     * @var \Mockery\MockInterface
     */
    private $session_manager;
    /**
     * @var \Mockery\MockInterface
     */
    private $revoker;
    /**
     * @var \Mockery\MockInterface
     */
    private $user;

    protected function setUp(): void
    {
        $this->user_manager    = \Mockery::mock(\UserManager::class);
        $this->session_manager = \Mockery::mock(SessionManager::class);
        $this->revoker         = \Mockery::mock(Revoker::class);
        $this->user            = \Mockery::mock(\PFUser::class);
    }

    public function testPasswordChangeInvalidateSessionsAndExistingResetTokens()
    {
        $password_changer = new PasswordChanger($this->user_manager, $this->session_manager, $this->revoker);

        $this->user->shouldReceive('setPassword')->once();
        $this->session_manager->shouldReceive('destroyAllSessionsButTheCurrentOne')->once();
        $this->revoker->shouldReceive('revokeTokens')->once();
        $this->user_manager->shouldReceive('updateDb')->once()->andReturns(true);

        $password_changer->changePassword($this->user, 'new_password');
    }

    public function testSessionsAndResetTokensAreInvalidatedBeforeUpdatingPassword()
    {
        $password_changer = new PasswordChanger($this->user_manager, $this->session_manager, $this->revoker);

        $this->user->shouldReceive('setPassword')->once();
        $this->session_manager->shouldReceive('destroyAllSessionsButTheCurrentOne')->once();
        $this->revoker->shouldReceive('revokeTokens')->once();
        $this->user_manager->shouldReceive('updateDb')->once()->andReturns(false);

        $this->expectException(PasswordChangeException::class);

        $password_changer->changePassword($this->user, 'new_password');
    }
}
