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

DROP TABLE IF EXISTS plugin_program_management_team_projects;
DROP TABLE IF EXISTS plugin_program_management_pending_mirrors;
DROP TABLE IF EXISTS plugin_program_management_plan;
DROP TABLE IF EXISTS plugin_program_management_can_prioritize_features;
DROP TABLE IF EXISTS plugin_program_management_explicit_top_backlog;
DROP TABLE IF EXISTS plugin_program_management_workflow_action_add_top_backlog;
DROP TABLE IF EXISTS plugin_program_management_program;

DELETE FROM service WHERE short_name = 'plugin_program_management';
