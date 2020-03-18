<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 *  Data Access Object for ArtifactGlobalNotification
 */
class ArtifactGlobalNotificationDao extends DataAccessObject
{
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM artifact_global_notification";
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by Id
    * @return DataAccessResult
    */
    public function searchById($id)
    {
        $sql = sprintf(
            "SELECT tracker_id, addresses, all_updates, check_permissions FROM artifact_global_notification WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by TrackerId
    * @return DataAccessResult
    */
    public function searchByTrackerId($trackerId)
    {
        $sql = sprintf(
            "SELECT id, addresses, all_updates, check_permissions FROM artifact_global_notification WHERE tracker_id = %s ORDER BY id",
            $this->da->quoteSmart($trackerId)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by Addresses
    * @return DataAccessResult
    */
    public function searchByAddresses($addresses)
    {
        $sql = sprintf(
            "SELECT id, tracker_id, all_updates, check_permissions FROM artifact_global_notification WHERE addresses = %s",
            $this->da->quoteSmart($addresses)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by AllUpdates
    * @return DataAccessResult
    */
    public function searchByAllUpdates($allUpdates)
    {
        $sql = sprintf(
            "SELECT id, tracker_id, addresses, check_permissions FROM artifact_global_notification WHERE all_updates = %s",
            $this->da->quoteSmart($allUpdates)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by CheckPermissions
    * @return DataAccessResult
    */
    public function searchByCheckPermissions($checkPermissions)
    {
        $sql = sprintf(
            "SELECT id, tracker_id, addresses, all_updates FROM artifact_global_notification WHERE check_permissions = %s",
            $this->da->quoteSmart($checkPermissions)
        );
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table artifact_global_notification
    * @return false|string|int true or id(auto_increment) if there is no error
    */
    public function create($tracker_id, $addresses, $all_updates, $check_permissions)
    {
        $sql = sprintf(
            "INSERT INTO artifact_global_notification (tracker_id, addresses, all_updates, check_permissions) VALUES (%s, %s, %s, %s)",
            $this->da->quoteSmart($tracker_id),
            $this->da->quoteSmart($addresses),
            $this->da->quoteSmart($all_updates),
            $this->da->quoteSmart($check_permissions)
        );
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($dar === false) {
                return false;
            }
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }

    public function modify($id, $values)
    {
        $updates = array();
        foreach ($values as $field => $value) {
            $updates[] = $field . ' = ' . $this->da->quoteSmart($value);
        }
        $sql = "UPDATE artifact_global_notification SET " . implode(', ', $updates) . " WHERE id = " . $this->da->quoteSmart($id);
        return $this->update($sql);
    }

    public function delete($id, $tracker_id)
    {
        $sql = sprintf(
            "DELETE FROM artifact_global_notification WHERE id = %s AND tracker_id = %s",
            $this->da->quoteSmart($id),
            $this->da->quoteSmart($tracker_id)
        );
        return $this->update($sql);
    }
}
