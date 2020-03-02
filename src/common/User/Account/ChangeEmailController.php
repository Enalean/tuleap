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
 *
 */

namespace Tuleap\User\Account;

use Tuleap\Layout\BaseLayout;

class ChangeEmailController
{
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\UserManager $user_manager, \EventManager $event_manager)
    {
        $this->user_manager  = $user_manager;
        $this->event_manager = $event_manager;
    }

    public function complete(\HTTPRequest $request, BaseLayout $response)
    {
        $this->event_manager->processEvent('before_change_email-complete', []);

        $confirmation_hash = $request->getValidated('confirm_hash', 'string', '');
        $current_user      = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            $url_redirect  = new \URLRedirect($this->event_manager);
            $return_to     = $this->getChangeCompleteUrl($confirmation_hash);
            $response->redirect($url_redirect->makeReturnToUrl('/account/login.php', $return_to));
        }

        if (! hash_equals($current_user->getConfirmHash(), $confirmation_hash)) {
            $response->addFeedback(\Feedback::ERROR, _('You are not the user who asked for email change'));
            $response->redirect('/');
        }

        $old_email_user = clone $current_user;
        $current_user->clearConfirmHash();
        $current_user->setEmail($old_email_user->getEmailNew());
        $current_user->setEmailNew($old_email_user->getEmail());

        $this->user_manager->updateDb($current_user);

        $response->header(['title' => _('Email change complete')]);

        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__.'/../../../templates/user');
        $renderer->renderToPage(
            'change-email-complete',
            [
                'realname' => $current_user->getRealName(),
                'email'    => $current_user->getEmail(),
            ]
        );

        $response->footer([]);
    }

    private function getChangeCompleteUrl($confirmation_hash)
    {
        return '/account/change_email-complete.php?confirm_hash='.$confirmation_hash;
    }
}
