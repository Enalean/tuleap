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

    public function __construct(EventDispatcherInterface $event_dispatcher, CSRFSynchronizerToken $csrf_token, UserManager $user_manager)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->csrf_token = $csrf_token;
        $this->user_manager = $user_manager;
    }

    public static function buildSelf(): self
    {
        return new self(
            EventManager::instance(),
            DisplayAccountInformationController::getCSRFToken(),
            UserManager::instance(),
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

        $wanted_realname = $request->get('realname');
        if ($wanted_realname && $account_information_collection->isUserAllowedToCanChangeRealName()) {
            $this->updateRealName($layout, $user, (string) $wanted_realname);
        }

        $layout->redirect(DisplayAccountInformationController::URL);
    }

    private function updateRealName(BaseLayout $layout, \PFUser $user, string $wanted_realname): void
    {
        if (strlen($wanted_realname) > \PFUser::REALNAME_MAX_LENGTH) {
            $layout->addFeedback(\Feedback::ERROR, _('Submitted real name is too long, it must be less than 32 characters'));
            return;
        }
        if ($wanted_realname === $user->getRealName()) {
            $layout->addFeedback(\Feedback::INFO, _('Nothing changed'));
            return;
        }

        $user->setRealName($wanted_realname);
        $this->user_manager->updateDb($user);

        $layout->addFeedback(\Feedback::INFO, _('Real name successfully updated'));
    }
}
