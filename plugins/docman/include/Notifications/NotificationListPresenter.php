<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use Codendi_HTMLPurifier;
use Docman_Item;
use Tuleap\Notifications\UserToBeNotifiedPresenter;
use UserManager;

class NotificationListPresenter
{
    public $has_listeners;
    public $notifications;
    public $notified_people;
    public $delete_button;
    public $monitored_doc;
    public $purified_help;
    public $empty_state;

    public function __construct(array $listeners, Docman_Item $item)
    {
        $this->has_listeners = count($listeners) > 0;

        $this->notifications = $this->buildNotificationsFromListeners($listeners, $item);

        $this->notified_people = dgettext('tuleap-docman', 'Notified people');
        $this->delete_button   = $GLOBALS['Language']->getText('plugin_docman', 'action_delete');
        $this->monitored_doc   = $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_monitored_doc');
        $this->empty_state     = $GLOBALS['Language']->getText('plugin_docman', 'empty_state');
        $this->purified_help   = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_help'),
            CODENDI_PURIFIER_LIGHT
        );
    }

    private function buildNotificationsFromListeners(array $listeners, Docman_Item $item)
    {
        $user_manager    = UserManager::instance();
        $users_to_notify = array();

        foreach ($listeners as $user_id => $monitored_item) {
            $user = $user_manager->getUserById($user_id);

            $users_to_notify[] = array(
                'can_be_deleted' => $monitored_item == $item,
                'item_title'     => $item->getTitle(),
                'user'           => new UserToBeNotifiedPresenter(
                    $user->getId(),
                    $user->getName(),
                    $user->getRealName(),
                    $user->hasAvatar(),
                    $user->getAvatarUrl()
                )
            );
        }

        return $users_to_notify;
    }
}
