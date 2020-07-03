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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\User\Account\PasswordUserPostUpdateEvent;
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
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var \Mockery\MockInterface
     */
    private $user;

    protected function setUp(): void
    {
        $this->user_manager     = \Mockery::mock(\UserManager::class);
        $this->session_manager  = \Mockery::mock(SessionManager::class);
        $this->revoker          = \Mockery::mock(Revoker::class);
        $this->event_dispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $this->user             = \Mockery::mock(\PFUser::class);
    }

    public function testPasswordChangeInvalidateSessionsAndExistingResetTokens(): void
    {
        $password_changer = new PasswordChanger(
            $this->user_manager,
            $this->session_manager,
            $this->revoker,
            $this->event_dispatcher,
            new DBTransactionExecutorPassthrough()
        );

        $this->user->shouldReceive('setPassword')->once();
        $this->session_manager->shouldReceive('destroyAllSessionsButTheCurrentOne')->once();
        $this->revoker->shouldReceive('revokeTokens')->once();
        $this->event_dispatcher->shouldReceive('dispatch')->once()->withArgs(
            function (PasswordUserPostUpdateEvent $event): bool {
                return $event->getUser() === $this->user;
            }
        );
        $this->user_manager->shouldReceive('updateDb')->once()->andReturns(true);

        $password_changer->changePassword($this->user, new ConcealedString('new_password'));
    }
}
