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

namespace Tuleap\User\Account\Register;

use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\InviteBuddy\InvalidInvitationTokenException;
use Tuleap\InviteBuddy\InvitationByTokenRetriever;
use Tuleap\InviteBuddy\InvitationNotFoundException;
use Tuleap\InviteBuddy\InvitationToEmail;
use Tuleap\Request\ForbiddenException;

final class InvitationToEmailRequestExtractor implements IExtractInvitationToEmail
{
    public function __construct(
        private InvitationByTokenRetriever $invitation_dao,
        private SplitTokenIdentifierTranslator $split_token_identifier,
    ) {
    }

    #[\Override]
    public function getInvitationToEmail(\Codendi_Request $request): ?InvitationToEmail
    {
        $token = $request->get('invitation-token');
        if (! \is_string($token)) {
            return null;
        }

        $token = new ConcealedString($token);
        try {
            $invitation = $this->invitation_dao->searchBySplitToken(
                $this->split_token_identifier->getSplitToken($token)
            );

            return InvitationToEmail::fromInvitation($invitation, $token);
        } catch (InvitationNotFoundException) {
            throw new ForbiddenException(_('Your invitation cannot be found. Maybe it became obsolete and has been revoked.'));
        } catch (InvalidInvitationTokenException $exception) {
            if ($exception->hasUserAlreadyBeenCreated()) {
                throw new ForbiddenException(_('Your invitation link is not valid. Maybe you already used it, in that case you can directly log in.'));
            }

            throw new ForbiddenException(_('Your invitation link is not valid'));
        } catch (
            InvalidIdentifierFormatException |
            InvitationShouldBeToEmailException
        ) {
            throw new ForbiddenException(_('Your invitation link is not valid'));
        }
    }
}
