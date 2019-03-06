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

use Tuleap\REST\Header;
use Tuleap\REST\UserManager;
use Tuleap\REST\AuthenticatedResource;
use Luracast\Restler\RestException;
use Tuleap\CallMeBack\CallMeBackMessageDao;

class CallMeBackMessageResource extends AuthenticatedResource
{
    /**
     * @var UserManager
     */
    private $rest_user_manager;
    /**
     * @var CallMeBackMessageDao
     */
    private $message_dao;

    public function __construct()
    {
        $this->rest_user_manager = UserManager::build();
        $this->message_dao       = new CallMeBackMessageDao();
    }
    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get message
     *
     * @access protected
     *
     * @url GET
     *
     * @throws 403
     *
     * @return MessageRepresentation
     */
    protected function get()
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        $current_user           = $this->rest_user_manager->getCurrentUser();
        $current_user_locale    = $current_user->getLocale();
        $message_representation = new MessageRepresentation();
        $message_content        = $this->message_dao->get($current_user_locale) ?: null;

        $message_representation->build($message_content);

        return $message_representation;
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }
}
