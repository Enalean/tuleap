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

namespace Tuleap\GitLFS\Transfer;

use Tuleap\Authentication\SplitToken\IncorrectSizeVerificationStringException;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationException;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationVerifier;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationType;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class LFSActionUserAccessHTTPRequestChecker
{
    /**
     * @var \gitlfsPlugin
     */
    private $plugin;
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $authorization_token_unserializer;
    /**
     * @var ActionAuthorizationVerifier
     */
    private $authorization_verifier;

    public function __construct(
        \gitlfsPlugin $plugin,
        SplitTokenIdentifierTranslator $authorization_token_unserializer,
        ActionAuthorizationVerifier $authorization_verifier
    ) {
        $this->plugin                           = $plugin;
        $this->authorization_token_unserializer = $authorization_token_unserializer;
        $this->authorization_verifier           = $authorization_verifier;
    }

    /**
     * @return \Tuleap\GitLFS\Authorization\Action\AuthorizedAction
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function userCanAccess(
        \HTTPRequest $request,
        ActionAuthorizationType $action_type,
        $oid
    ) {
        $authorization_header = $request->getFromServer('HTTP_AUTHORIZATION');
        if ($authorization_header === false) {
            throw new ForbiddenException();
        }

        try {
            $authorization_token = $this->authorization_token_unserializer->getSplitToken(
                new ConcealedString($authorization_header)
            );
        } catch (IncorrectSizeVerificationStringException $ex) {
            throw new ForbiddenException();
        } catch (InvalidIdentifierFormatException $ex) {
            throw new ForbiddenException();
        }

        try {
            $authorized_action = $this->authorization_verifier->getAuthorization(
                new \DateTimeImmutable(),
                $authorization_token,
                $oid,
                $action_type
            );
        } catch (ActionAuthorizationException $ex) {
            throw new ForbiddenException();
        }

        $repository = $authorized_action->getRepository();
        if (
            $repository === null || ! $repository->isCreated() || ! $repository->getProject()->isActive() ||
            ! $this->plugin->isAllowed($repository->getProject()->getID())
        ) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        return $authorized_action;
    }
}
