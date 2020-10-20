/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

CREATE TABLE plugin_scaled_agile_team_projects(
    program_project_id INT(11) NOT NULL,
    team_project_id INT(11) NOT NULL,
    PRIMARY KEY (program_project_id, team_project_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_scaled_agile_pending_mirrors(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    program_artifact_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    changeset_id INT(11) NOT NULL
) ENGINE=InnoDB;
