<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy\Admin;

use Tuleap\Config\ConfigDao;
use HTTPRequest;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class InviteBuddyAdminUpdateController implements DispatchableWithRequest
{
    public function __construct(
        private CSRFSynchronizerTokenInterface $csrf_token,
        private InviteBuddyConfiguration $configuration,
        private ConfigDao $dao,
    ) {
    }

    public static function buildSelf(): self
    {
        return new self(
            InviteBuddyAdminController::getCSRFSynchronizerToken(),
            new InviteBuddyConfiguration(\EventManager::instance()),
            new ConfigDao(),
        );
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check();

        $submitted_max_invitations_by_day = (int) $request->get('max_invitations_by_day');
        if ($submitted_max_invitations_by_day === $this->configuration->getNbMaxInvitationsByDay()) {
            $layout->addFeedback(
                \Feedback::INFO,
                _('Nothing changed')
            );
            $this->redirect($layout);

            return;
        }

        if ($submitted_max_invitations_by_day <= 0) {
            $layout->addFeedback(
                \Feedback::ERROR,
                _('Users must be able to send at least one invitation by day.')
            );
            $this->redirect($layout);

            return;
        }

        $this->dao->saveInt(
            InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY,
            $submitted_max_invitations_by_day
        );

        $layout->addFeedback(
            \Feedback::INFO,
            _('Invitations settings successfully updated.')
        );

        $this->redirect($layout);
    }

    private function redirect(BaseLayout $layout): void
    {
        $layout->redirect(InviteBuddyAdminController::URL);
    }
}
