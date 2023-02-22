/**
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

export interface Config {
    readonly has_servers: boolean;
    readonly create_url: string;
    readonly base_url: string;
    readonly csrf_token: CsrfToken;
    readonly servers: ReadonlyArray<Server>;
}

interface CsrfToken {
    readonly name: string;
    readonly token: string;
}

export interface Server {
    readonly id: number;
    readonly server_url: string;
    readonly delete_url: string;
    readonly update_url: string;
    readonly restrict_url: string;
    readonly has_existing_secret: boolean;
    readonly is_project_restricted: boolean;
    readonly project_restrictions: ReadonlyArray<Project>;
}

export interface Project {
    readonly id: number;
    readonly label: string;
    readonly url: string;
}

export interface ProjectFromRest {
    readonly id: number;
    readonly label: string;
    readonly shortname: string;
}

export interface Navigation {
    readonly restrict: (server: Server) => void;
    readonly cancelRestriction: () => void;
}
