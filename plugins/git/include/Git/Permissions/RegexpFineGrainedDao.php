<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

use Tuleap\DB\DataAccessObject;

class RegexpFineGrainedDao extends DataAccessObject
{
    public function areRegexpActivatedAtSiteLevel()
    {
        $sql = 'SELECT COUNT(*) FROM plugin_git_fine_grained_regexp_enabled';

        return $this->getDB()->single($sql) > 0;
    }

    public function enable()
    {
        $sql = 'INSERT INTO plugin_git_fine_grained_regexp_enabled (enabled) VALUES (1)';

        try {
            $this->getDB()->run($sql);
        } catch (\PDOException $ex) {
            return false;
        }

        return true;
    }

    public function disable()
    {
        $sql = 'DELETE FROM plugin_git_fine_grained_regexp_enabled';

        try {
            $this->getDB()->run($sql);
        } catch (\PDOException $ex) {
            return false;
        }

        return true;
    }
}
