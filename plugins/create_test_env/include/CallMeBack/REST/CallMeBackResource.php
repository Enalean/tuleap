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

namespace Tuleap\CallMeBack\REST;

use PFUser;
use EventManager;
use StandardPasswordHandler;
use User_LoginManager;
use User_PasswordExpirationChecker;
use Tuleap\REST\Header;
use Tuleap\REST\UserManager;
use Tuleap\REST\AuthenticatedResource;
use Luracast\Restler\RestException;
use Tuleap\CallMeBack\CallMeBackEmailNotifier;
use Tuleap\CallMeBack\CallMeBackEmailDao;
use Tuleap\CallMeBack\Exception\NotifyException;

class CallMeBackResource extends AuthenticatedResource
{
    /**
     * @var UserManager
     */
    private $rest_user_manager;

    /**
     * @var CallMeBackEmailNotifier
     */
    private $notifier;

    public function __construct()
    {
        $this->rest_user_manager = UserManager::build();
        $this->notifier          = new CallMeBackEmailNotifier(
            new CallMeBackEmailDao()
        );
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeaders();
    }

    /**
     * Send an email with call me back information
     *
     * @param string $phone User phone number {@from body} {@type string}
     * @param string $date  Date when user want to be called back {@from body} {@type date}
     *
     * @access protected
     *
     * @url POST
     * @status 201
     *
     * @throws 403
     * @throws 500
     */
    public function post($phone, $date)
    {
        try {
            $this->checkAccess();

            $current_user = $this->rest_user_manager->getCurrentUser();

            $this->notifier->notify($current_user, $phone, $date);

            $current_user->setPreference('plugin_call_me_back_asked_to_be_called_back', '1');
        } catch (NotifyException $exception) {
            throw new RestException(500, "Unable to notify the people who will call back");
        } finally {
            $this->sendAllowHeaders();
        }
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsPost();
    }
}
