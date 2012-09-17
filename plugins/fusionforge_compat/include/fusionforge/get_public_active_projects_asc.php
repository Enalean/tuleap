<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * get_public_active_projects_asc() - Get a list of rows for public active projects (initially in trove/full_list)
 *
 * @param  int Opional Maximum number of rows to limit query length·
 */
function get_public_active_projects_asc($max_query_limit = -1) {

	$res_grp = db_query("
        SELECT group_id, group_name, unix_group_name, short_description, register_time
        FROM groups
        WHERE status = 'A' AND is_public=1 AND group_id>4 AND register_time > 0
        ORDER BY group_name ASC
			");
	$projects = array();
	while ($row_grp = db_fetch_array($res_grp)) {
		if (!forge_check_perm ('project_read', $row_grp['group_id'])) {
			continue ;
		}
		$projects[] = $row_grp;
	}
	return $projects;
}

