<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('ArtifactDateReminderFactory.class.php');

// The artifact date reminder object
class ArtifactDateReminder
{
    private $logger;

    public function __construct(TrackerDateReminder_Logger $logger)
    {
        $this->logger = new TrackerDateReminder_Logger_Prefix($logger, '');
    }

    public function codexDaily()
    {
        $this->logger->info('Start');

        $sql = "SELECT notification_id FROM artifact_date_reminder_processing ORDER BY notification_id";
        $res = db_query($sql);
        if (db_numrows($res) > 0) {
            while ($rows = db_fetch_array($res)) {
                $notification_id = $rows['notification_id'];
                // For each event(represented by a row in artifact_date_reminder_processing table),
                // instantiate a new ArtifactDateReminderFactory, then check its reminder status
                $adrf = new ArtifactDateReminderFactory($notification_id, $this->logger);
                $adrf->checkReminderStatus(\Tuleap\Request\RequestTime::getTimestamp());
            }
        }

        $this->logger->info('End');
    }
}
