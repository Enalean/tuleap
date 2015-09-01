<?php
/**
 * Copyright Enalean (c) 2015. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once 'common/autoload.php';

class SystemEvent_PurgerTest extends TuleapDbTestCase {

    /** @var SystemEventPurger */
    private $system_event_purger;

    public function __construct() {
        parent::__construct();
        $this->initDb();
        $system_event_dao          = new SystemEventDao();
        $this->system_event_purger = new SystemEventPurger($system_event_dao);
    }

    public function itPurgeSystemEventsDataOlderThanOneYear() {
        $this->purgeTable();
        $this->insertHugeData();
        $this->assertEqual($this->getNumberOfEvents(), 6);
        $this->system_event_purger->purgeSystemEventsDataOlderThanOneYear();
        $this->assertEqual($this->getNumberOfEvents(), 3);
        $this->purgeTable();
    }

    private function insertHugeData() {
        echo 'Start generating data' . PHP_EOL;
        $dates = array();

        $dates[] = date('Y-m-d 00:00:00', strtotime('-1 year', time()));
        $dates[] = date('Y-m-d 00:00:00', strtotime('-2 years', time()));
        $dates[] = date('Y-m-d 00:00:00', strtotime('-3 years', time()));
        $dates[] = date('Y-m-d 00:00:00', strtotime('-2 days', time()));
        $dates[] = date('Y-m-d 00:00:00', strtotime('-1 day', time()));
        $dates[] = date('Y-m-d 00:00:00', strtotime('-1 year -1 day', time()));

        foreach ($dates as $date) {
            $this->insertSystemEvents($date);
            echo '.';
        }

        echo PHP_EOL . 'Data generated' . PHP_EOL;
    }

    private function insertSystemEvents($date) {
        $sql = "INSERT INTO system_event (type, parameters, priority, status, create_date, owner)
                VALUES ('SYSTEM_CHECK', '', '3', 3, '$date', 'root')";

        $this->mysqli->query($sql);
    }

    private function purgeTable() {
        $sql = "TRUNCATE TABLE system_event";

        $this->mysqli->query($sql);
    }

    private function getNumberOfEvents() {
        $result = $this->mysqli->query("SELECT * FROM system_event");

        return $result->num_rows;
    }

}
