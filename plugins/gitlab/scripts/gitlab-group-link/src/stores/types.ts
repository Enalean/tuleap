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

export interface RootState {
    current_project: Project;
}

export interface Project {
    readonly public_name: string;
}

export interface GroupsState {
    groups: readonly GitlabGroup[];
}

export interface GitlabGroup {
    readonly id: string;
    readonly name: string;
    readonly avatar_url: string | null;
    readonly full_path: string;
}

export interface GitlabCredentialsInit {
    readonly server_url: URL | "";
    readonly token: string;
}

export interface GitlabCredentials extends GitlabCredentialsInit {
    readonly server_url: URL;
}

export interface CredentialsState {
    credentials: GitlabCredentialsInit;
}
