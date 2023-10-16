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

namespace Tuleap\User\Password\Change;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\User\Account\PasswordUserPostUpdateEvent;
use Tuleap\User\Password\Reset\Revoker;
use Tuleap\User\SessionManager;

final class PasswordChangerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testPasswordChangeInvalidateSessionsAndExistingResetTokens(): void
    {
        $user_manager     = $this->createMock(\UserManager::class);
        $session_manager  = $this->createMock(SessionManager::class);
        $revoker          = $this->createMock(Revoker::class);
        $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
        $user             = $this->createMock(\PFUser::class);

        $password_changer = new PasswordChanger(
            $user_manager,
            $session_manager,
            $revoker,
            $event_dispatcher,
            new DBTransactionExecutorPassthrough()
        );

        $user->expects(self::once())->method('setPassword');
        $session_manager->expects(self::once())->method('destroyAllSessionsButTheCurrentOne');
        $revoker->expects(self::once())->method('revokeTokens');
        $event_dispatcher->expects(self::once())->method('dispatch')->with(
            self::callback(
                function (PasswordUserPostUpdateEvent $event) use ($user): bool {
                    return $event->getUser() === $user;
                }
            )
        );
        $user_manager->expects(self::once())->method('updateDb')->willReturn(true);

        $password_changer->changePassword($user, new ConcealedString('new_password'));
    }
}
