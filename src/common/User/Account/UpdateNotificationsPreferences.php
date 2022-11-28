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

namespace Tuleap\User\Account;

use Codendi_Mail_Interface;
use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserManager;

class UpdateNotificationsPreferences implements DispatchableWithRequest
{
    public function __construct(private CSRFSynchronizerToken $csrf_token, private UserManager $user_manager, private EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check(DisplayNotificationsController::URL);

        $user_need_update  = false;
        $something_changed = false;

        $site_email_updates = $request->get('site_email_updates') === '1' ? 1 : 0;
        if ($site_email_updates !== (int) $user->getMailSiteUpdates()) {
            $user->setMailSiteUpdates($site_email_updates);
            $user_need_update = true;
        }

        $site_email_community = $request->get('site_email_community') === '1' ? 1 : 0;
        if ($site_email_community !== (int) $user->getMailVA()) {
            $user->setMailVA($site_email_community);
            $user_need_update = true;
        }

        if ($request->exist('email_format')) {
            $format_email = $request->get('email_format') === Codendi_Mail_Interface::FORMAT_HTML ? Codendi_Mail_Interface::FORMAT_HTML : Codendi_Mail_Interface::FORMAT_TEXT;
            if ($format_email !== $user->getPreference(Codendi_Mail_Interface::PREF_FORMAT)) {
                $user->setPreference(Codendi_Mail_Interface::PREF_FORMAT, $format_email);
                $layout->addFeedback(Feedback::INFO, _('Email format preference successfully updated'));
                $something_changed = true;
            }
        }

        $notificationsOnOwnActionsUpdate = $this->dispatcher->dispatch(new NotificationsOnOwnActionsUpdate($user, $request));

        if ($user_need_update) {
            if (! $this->user_manager->updateDb($user)) {
                $layout->addFeedback(Feedback::ERROR, _('Unable to update user preferences'));
            } else {
                $layout->addFeedback(Feedback::INFO, _('User preferences successfully updated'));
            }
        } elseif (! $something_changed && ! $notificationsOnOwnActionsUpdate->something_has_changed) {
            $layout->addFeedback(Feedback::INFO, _('Nothing changed'));
        }

        $layout->redirect(DisplayNotificationsController::URL);
    }
}
