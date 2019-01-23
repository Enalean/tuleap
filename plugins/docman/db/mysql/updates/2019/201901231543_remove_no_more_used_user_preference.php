<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

class b201901231543_remove_no_more_used_user_preference extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Remove no more used use preference';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "DELETE FROM user_preferences WHERE preference_name LIKE 'plugin_docman_display_legacy_ui_%'";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $this->rollBackOnError('Can not delete the legacy ui preference');
        }
    }
}
