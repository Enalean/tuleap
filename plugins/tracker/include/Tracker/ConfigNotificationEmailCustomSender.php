<?php
/**
 * Copyright (c) Ericsson AB, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use Tracker;

class ConfigNotificationEmailCustomSender
{

    /**
     * @var ConfigNotificationAssignedToDao
     */
    private $dao;

    public function __construct($dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return mixed array(format, enabled) if found, false otherwise
     */
    public function getCustomSender(Tracker $tracker)
    {
        $rows = $this->dao->searchCustomSender($tracker->getId());
        if (count($rows) <= 0) {
            return ['format' => '', 'enabled' => 0];
        } else {
            return $rows[0]; // Only one answer is expected for any one tracker_id
        }
    }

    public function setCustomSender(Tracker $tracker, $format, $enabled)
    {
        $enabled = $enabled ? 1 : 0;
        $this->dao->create($tracker->getId(), $format, $enabled);
    }
}
