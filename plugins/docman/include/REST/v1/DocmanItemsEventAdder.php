<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_Log;
use Project;
use Tuleap\Docman\Log\LogEventAdder;
use Tuleap\Docman\Notifications\NotificationBuilders;
use Tuleap\Docman\Notifications\NotificationEventAdder;

class DocmanItemsEventAdder
{
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function addNotificationEvents(Project $project): void
    {
        $feedback                         = new NullResponseFeedbackWrapper();
        $notifications_builders           = new NotificationBuilders(
            $feedback,
            $project
        );
        $notification_manager             = $notifications_builders->buildNotificationManager();
        $notification_manager_add         = $notifications_builders->buildNotificationManagerAdd();
        $notification_manager_delete      = $notifications_builders->buildNotificationManagerDelete();
        $notification_manager_move        = $notifications_builders->buildNotificationManagerMove();
        $notification_manager_subscribers = $notifications_builders->buildNotificationManagerSubsribers();

        $adder = new NotificationEventAdder(
            $this->event_manager,
            $notification_manager,
            $notification_manager_add,
            $notification_manager_delete,
            $notification_manager_move,
            $notification_manager_subscribers
        );

        $adder->addNotificationManagement();
    }

    public function addLogEvents(): void
    {
        $logger = new Docman_Log();
        $adder  = new LogEventAdder($this->event_manager, $logger);

        $adder->addLogEventManagement();
    }
}
