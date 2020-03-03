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

use CSRFSynchronizerToken;
use EventManager;
use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserManager;

final class UpdateAccountInformationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/account/information';
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var EmailUpdater
     */
    private $email_updater;

    public function __construct(EventDispatcherInterface $event_dispatcher, CSRFSynchronizerToken $csrf_token, UserManager $user_manager, EmailUpdater $email_updater)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->csrf_token = $csrf_token;
        $this->user_manager = $user_manager;
        $this->email_updater = $email_updater;
    }

    public static function buildSelf(): self
    {
        return new self(
            EventManager::instance(),
            DisplayAccountInformationController::getCSRFToken(),
            UserManager::instance(),
            new EmailUpdater(),
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check(DisplayAccountInformationController::URL);

        $account_information_collection = $this->event_dispatcher->dispatch(new AccountInformationCollection($user));
        assert($account_information_collection instanceof AccountInformationCollection);

        $something_changed = false;

        $wanted_realname = $request->get('realname');
        if ($wanted_realname && $account_information_collection->isUserAllowedToCanChangeRealName()) {
            $something_changed = $this->updateRealName($layout, $user, (string) $wanted_realname) || $something_changed;
        }

        $wanted_email = $request->getValidated('email', new \Valid_Email(), false);
        if ($wanted_email && $account_information_collection->isUserAllowedToChangeEmail()) {
            $something_changed = $this->updateEmail($request, $layout, $user, (string) $wanted_email) || $something_changed;
        }

        $wanted_timezone = $request->get('timezone');
        if ($wanted_timezone) {
            $something_changed = $this->updateTimezone($layout, $user, (string) $wanted_timezone) || $something_changed;
        }

        if (! $something_changed) {
            $layout->addFeedback(\Feedback::INFO, _('Nothing changed'));
        }

        $layout->redirect(DisplayAccountInformationController::URL);
    }

    private function updateRealName(BaseLayout $layout, \PFUser $user, string $wanted_realname): bool
    {
        if (strlen($wanted_realname) > \PFUser::REALNAME_MAX_LENGTH) {
            $layout->addFeedback(\Feedback::ERROR, _('Submitted real name is too long, it must be less than 32 characters'));
            return false;
        }
        if ($wanted_realname === $user->getRealName()) {
            return false;
        }

        $user->setRealName($wanted_realname);
        if ($this->user_manager->updateDb($user)) {
            $layout->addFeedback(\Feedback::INFO, _('Real name successfully updated'));
            return true;
        }

        $layout->addFeedback(\Feedback::ERROR, _('Real name was not updated'));
        return false;
    }

    private function updateEmail(HTTPRequest $request, BaseLayout $layout, \PFUser $user, string $wanted_email): bool
    {
        try {
            if ($user->getEmail() === $wanted_email) {
                return false;
            }

            $user->setEmailNew($wanted_email);
            $user->setConfirmHash((new \RandomNumberGenerator())->getNumber());

            if ($this->user_manager->updateDb($user)) {
                $this->email_updater->sendEmailChangeConfirm($request->getServerUrl(), $user);

                $layout->addFeedback(\Feedback::INFO, _('New email was successfully saved. To complete the change, <strong>please click on the confirmation link</strong> you will receive by email (new address).'), CODENDI_PURIFIER_LIGHT);
                return true;
            } else {
                $layout->addFeedback(\Feedback::ERROR, _('Email was not updated'));
            }
        } catch (EmailNotSentException $exception) {
            $layout->addFeedback(\Feedback::ERROR, $exception->getMessage());
            return true;
        }
        return false;
    }

    private function updateTimezone(BaseLayout $layout, \PFUser $user, string $wanted_timezone): bool
    {
        if (! (new \Account_TimezonesCollection())->isValidTimezone($wanted_timezone)) {
            $layout->addFeedback(\Feedback::ERROR, 'Invalid timezone');
            return false;
        }

        if ($user->getTimezone() === $wanted_timezone) {
            return false;
        }

        $user->setTimezone($wanted_timezone);
        if ($this->user_manager->updateDb($user)) {
            $layout->addFeedback(\Feedback::INFO, _('Timezone successfully updated'));
            return true;
        }
        $layout->addFeedback(\Feedback::ERROR, _('Timezone was not updated'));
        return false;
    }
}
