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

use EventManager;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserManager;

final class ConfirmNewEmailController implements DispatchableWithRequest
{
    public const URL = '/account/confirm-new-email';

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(UserManager $user_manager, EventManager $event_manager)
    {
        $this->user_manager  = $user_manager;
        $this->event_manager = $event_manager;
    }

    public static function buildSelf()
    {
        return new self(
            UserManager::instance(),
            EventManager::instance(),
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $confirmation_hash = $request->getValidated('confirm_hash', 'string', '');
        $current_user      = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            $url_redirect  = new \URLRedirect($this->event_manager);
            $return_to     = self::getUrlToSelf($confirmation_hash);
            $layout->redirect($url_redirect->makeReturnToUrl('/account/login.php', $return_to));
        }

        $account_information_collection = $this->event_manager->dispatch(new AccountInformationCollection($current_user));
        assert($account_information_collection instanceof AccountInformationCollection);
        if (! $account_information_collection->isUserAllowedToChangeEmail()) {
            throw new ForbiddenException();
        }

        if (! hash_equals($current_user->getConfirmHash(), $confirmation_hash)) {
            $layout->addFeedback(\Feedback::ERROR, _('You are not the user who asked for email change'));
            $layout->redirect('/');
        }

        $old_email_user = clone $current_user;
        $current_user->clearConfirmHash();
        $current_user->setEmail($old_email_user->getEmailNew());
        $current_user->setEmailNew($old_email_user->getEmail());

        $this->user_manager->updateDb($current_user);

        $layout->addFeedback(\Feedback::INFO, sprintf(_('Your email change is complete. Your new email address is <strong>%s</strong>'), $current_user->getEmail()), \Codendi_HTMLPurifier::CONFIG_LIGHT);

        $layout->redirect(DisplayAccountInformationController::URL);
    }

    public static function getUrlToSelf(string $confirm_hash)
    {
        return sprintf('%s?confirm_hash=%s', self::URL, $confirm_hash);
    }
}
