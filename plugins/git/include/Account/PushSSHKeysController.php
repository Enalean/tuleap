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
 *
 */

declare(strict_types=1);

namespace Tuleap\Git\Account;

use Git_UserAccountManager;
use Git_UserSynchronisationException;
use HTTPRequest;
use Psr\Log\LoggerInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

final readonly class PushSSHKeysController implements DispatchableWithRequest
{
    public function __construct(
        private CSRFSynchronizerTokenInterface $csrf_token,
        private Git_UserAccountManager $git_user_account_manager,
        private \Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check(AccountGerritController::URL);

        if (count($this->gerrit_server_factory->getRemoteServersForUser($user)) === 0) {
            throw new ForbiddenException();
        }

        $this->logger->info('Trying to push ssh keys for user: ' . $user->getUserName());

        try {
            $this->git_user_account_manager->pushSSHKeys($user);
        } catch (Git_UserSynchronisationException $e) {
            $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-git', 'Error pushing SSH Keys. Please add them manually.'));

            $this->logger->error('Unable to push ssh keys: ' . $e->getMessage());
        }

        $this->logger->info('Successfully pushed ssh keys for user: ' . $user->getUserName());
        $layout->redirect(AccountGerritController::URL);
    }
}
