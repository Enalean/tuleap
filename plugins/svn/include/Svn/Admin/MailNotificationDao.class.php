<?php
/**
  * Copyright (c) Enalean, 2016. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

namespace Tuleap\Svn\Admin;

use DataAccessObject;
use Project;

class MailNotificationDao extends DataAccessObject {

    public function searchByRepositoryId($repository_id) {
        $repository_id = $this->da->escapeInt($repository_id);
        $sql = "SELECT *
                FROM plugin_svn_notification
                WHERE repository_id=$repository_id";

        return $this->retrieve($sql);
    }

    public function deleteSvnMailingList($repository_id, $path) {
        $path          = $this->da->quoteSmart($path);
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "DELETE FROM plugin_svn_notification
                WHERE svn_path      = $path
                  AND repository_id = $repository_id";
        return $this->update($sql);
    }

    public function create(MailNotification $mail_notification) {
        $mailing_list  = $this->da->quoteSmart($mail_notification->getMailingList());
        $path          = $this->da->quoteSmart($mail_notification->getPath());
        $repository_id = $this->da->escapeInt($mail_notification->getRepository()->getId());

        $query = "REPLACE INTO plugin_svn_notification
                    (repository_id, mailing_list, svn_path)
                  VALUES
                    ($repository_id, $mailing_list, $path)";

        return $this->update($query);
    }

}