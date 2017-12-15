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
 *
 */

namespace Tuleap\Tracker\Artifact\Changeset\Notification;

use Logger;
use ForgeConfig;

class AsynchronousSupervisor
{
    const ACCEPTABLE_PROCESS_DELAY = 120;

    /**
     * @var NotifierDao
     */
    private $dao;

    public function __construct(NotifierDao $dao)
    {
        $this->dao = $dao;
    }

    public function runSystemCheck(Logger $logger)
    {
        if (ForgeConfig::get('sys_async_emails') !== false) {
            $last_end_date = $this->dao->getLastEndDate();
            $nb_pending_notifications = $this->dao->searchPendingNotificationsAfter($last_end_date + self::ACCEPTABLE_PROCESS_DELAY);
            if ($nb_pending_notifications > 0) {
                $logger->warn("There are ".$nb_pending_notifications." notifications pending, you should check '/usr/share/tuleap/plugins/tracker/bin/notify.php' and it's log file to ensure it's still running.");
            }
        }
    }
}
