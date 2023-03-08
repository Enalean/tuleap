<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\LostPassword;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\User\Password\Reset\ExpiredTokenException;

final class UserFromConfirmationHashRetriever
{
    public function __construct(
        private \Tuleap\User\Password\Reset\ResetTokenSerializer $reset_token_unserializer,
        private \Tuleap\User\Password\Reset\Verifier $reset_token_verifier,
    ) {
    }

    /**
     * @return Ok<\PFUser>|Err<Fault>
     */
    public function getUserFromConfirmationHash(ConcealedString $confirm_hash): Ok|Err
    {
        try {
            $token = $this->reset_token_unserializer->getSplitToken($confirm_hash);
            $user  = $this->reset_token_verifier->getUser($token);
        } catch (ExpiredTokenException $ex) {
            return Result::err(Fault::fromThrowableWithMessage(
                $ex,
                _('The confirmation key is expired, please renew if needed your request for the retrieval of your lost password')
            ));
        } catch (\Exception $ex) {
            return Result::err(Fault::fromThrowableWithMessage(
                $ex,
                _('Invalid confirmation hash.')
            ));
        }

        if ($user === null || $user->getUserPw() === null) {
            return Result::err(Fault::fromMessage(
                _('Invalid confirmation hash.')
            ));
        }

        return Result::ok($user);
    }
}
