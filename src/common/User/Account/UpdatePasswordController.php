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
use Exception;
use Feedback;
use HTTPRequest;
use PasswordHandlerFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use RandomNumberGenerator;
use SessionDao;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Layout\BaseLayout;
use Tuleap\Password\PasswordSanityChecker;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Password\Change\PasswordChanger;
use Tuleap\User\Password\Reset\DataAccessObject;
use Tuleap\User\Password\Reset\Revoker;
use Tuleap\User\PasswordVerifier;
use Tuleap\User\SessionManager;
use User_StatusInvalidException;
use User_UserStatusManager;
use UserManager;

final class UpdatePasswordController implements DispatchableWithRequest
{
    public const URL = '/account/security/password';

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var PasswordVerifier
     */
    private $password_verifier;
    /**
     * @var User_UserStatusManager
     */
    private $user_status_manager;
    /**
     * @var PasswordChanger
     */
    private $password_changer;
    /**
     * @var PasswordSanityChecker
     */
    private $password_sanity_checker;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        EventDispatcherInterface $event_dispatcher,
        CSRFSynchronizerToken $csrf_token,
        PasswordVerifier $password_verifier,
        User_UserStatusManager $user_status_manager,
        PasswordChanger $password_changer,
        PasswordSanityChecker $password_sanity_checker
    ) {
        $this->event_dispatcher = $event_dispatcher;
        $this->csrf_token = $csrf_token;
        $this->password_verifier = $password_verifier;
        $this->user_status_manager = $user_status_manager;
        $this->password_changer = $password_changer;
        $this->password_sanity_checker = $password_sanity_checker;
    }

    public static function buildSelf(): self
    {
        return new self(
            EventManager::instance(),
            DisplaySecurityController::getCSRFToken(),
            new PasswordVerifier(
                PasswordHandlerFactory::getPasswordHandler()
            ),
            new User_UserStatusManager(),
            new PasswordChanger(
                UserManager::instance(),
                new SessionManager(UserManager::instance(), new SessionDao(), new RandomNumberGenerator()),
                new Revoker(new DataAccessObject())
            ),
            PasswordSanityChecker::build(),
        );
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

        $this->csrf_token->check(DisplaySecurityController::URL);

        try {
            $password_pre_update_event = $this->event_dispatcher->dispatch(new PasswordPreUpdateEvent($user));
            assert($password_pre_update_event instanceof PasswordPreUpdateEvent);

            if (! $password_pre_update_event->areUsersAllowedToChangePassword()) {
                throw new UpdatePasswordException(_('Platform configuration forbid users to change password'));
            }

            $this->user_status_manager->checkStatus($user);

            $new_password = new ConcealedString((string) $request->get('new_password'));
            $repeat_new_password = new ConcealedString((string) $request->get('repeat_new_password'));

            if (! $new_password->isIdenticalTo($repeat_new_password)) {
                throw new UpdatePasswordException(_('Passwords do not match'));
            }

            if ($password_pre_update_event->isOldPasswordRequiredToUpdatePassword()) {
                $old_password = new ConcealedString((string) $request->get('current_password'));
                if (! $this->password_verifier->verifyPassword($user, $old_password->getString())) {
                    throw new UpdatePasswordException(_('Current password is incorrect'));
                }

                if ($new_password->isIdenticalTo($old_password)) {
                    throw new UpdatePasswordException(_('Current and new passwords are identical'));
                }
            }

            if (! $this->password_sanity_checker->check($new_password->getString())) {
                throw new UpdatePasswordSanityCheckerException($this->password_sanity_checker->getErrors());
            }

            $this->password_changer->changePassword($user, $new_password->getString());

            $layout->addFeedback(Feedback::INFO, _('Password successfully updated'));
        } catch (User_StatusInvalidException $exception) {
            $layout->addFeedback(Feedback::ERROR, _('Account must be active to change password'));
        } catch (UpdatePasswordException $exception) {
            $layout->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (UpdatePasswordSanityCheckerException $exception) {
            foreach ($exception->getErrors() as $error) {
                $layout->addFeedback(Feedback::ERROR, $error);
            }
        } catch (Exception $exception) {
            $layout->addFeedback(Feedback::ERROR, _('Internal error: Could not update password'));
        }

        $layout->redirect(DisplaySecurityController::URL);
    }
}
