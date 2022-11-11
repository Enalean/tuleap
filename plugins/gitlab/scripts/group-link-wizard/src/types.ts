/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

export type GitlabGroupLinkStepName =
    | "no-group-linked-empty-state"
    | "gitlab-server"
    | "gitlab-group"
    | "gitlab-configuration";

export const NO_GROUP_LINKED_EMPTY_STATE: GitlabGroupLinkStepName = "no-group-linked-empty-state";
export const STEP_GITLAB_SERVER: GitlabGroupLinkStepName = "gitlab-server";
export const STEP_GITLAB_GROUP: GitlabGroupLinkStepName = "gitlab-group";
export const STEP_GITLAB_CONFIGURATION: GitlabGroupLinkStepName = "gitlab-configuration";
