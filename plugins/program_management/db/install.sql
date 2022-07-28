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

CREATE TABLE plugin_program_management_team_projects(
    program_project_id INT(11) NOT NULL,
    team_project_id INT(11) NOT NULL,
    PRIMARY KEY (program_project_id, team_project_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_program_management_plan(
    project_id INT(11) NOT NULL,
    plannable_tracker_id INT(11) NOT NULL,
    PRIMARY KEY (project_id, plannable_tracker_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_program_management_program(
    program_project_id INT(11) NOT NULL,
    program_increment_tracker_id INT(11) NOT NULL,
    iteration_tracker_id INT(11) DEFAULT NULL,
    iteration_label VARCHAR(255) DEFAULT NULL,
    iteration_sub_label VARCHAR(255) DEFAULT NULL,
    program_increment_label VARCHAR(255) DEFAULT NULL,
    program_increment_sub_label VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (program_project_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_program_management_can_prioritize_features(
    project_id INT(11) NOT NULL,
    user_group_id INT(11) NOT NULL,
    PRIMARY KEY (project_id, user_group_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_program_management_explicit_top_backlog(
    artifact_id INT(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE plugin_program_management_workflow_action_add_top_backlog (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    transition_id INT(11) NOT NULL,
    INDEX idx_transition_id (transition_id)
) ENGINE = InnoDB;

CREATE TABLE plugin_program_management_team_synchronizations_pending (
    program_id INT(11) NOT NULL,
    team_id INT(11) NOT NULL,
    timestamp INT(11) NOT NULL
) ENGINE = InnoDB;

-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
SELECT DISTINCT group_id , 'plugin_program_management:service_lbl_key', 'plugin_program_management:service_desc_key', 'plugin_program_management', NULL, 1 , 0 , 'system',  153
FROM service
WHERE short_name != 'plugin_program_management';
