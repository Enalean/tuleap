/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import type { ProjectFlag } from "@tuleap/vue-breadcrumb-privacy";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";

let administration_url: string,
    repository_list_url: string,
    fork_repositories_url: string,
    project_public_name: string,
    project_url: string,
    privacy: ProjectPrivacy,
    project_flags: Array<ProjectFlag>,
    project_icon: string;

export function setBreadcrumbSettings(
    admin_url: string,
    repositories_url: string,
    fork_url: string,
    proj_public_name: string,
    proj_url: string,
    proj_privacy: ProjectPrivacy,
    proj_flags: Array<ProjectFlag>,
    proj_icon: string,
): void {
    administration_url = admin_url;
    repository_list_url = repositories_url;
    fork_repositories_url = fork_url;
    project_public_name = proj_public_name;
    project_url = proj_url;
    privacy = proj_privacy;
    project_flags = proj_flags;
    project_icon = proj_icon;
}

export function getAdministrationUrl(): string {
    return administration_url;
}

export function getRepositoryListUrl(): string {
    return repository_list_url;
}

export function getForkRepositoriesUrl(): string {
    return fork_repositories_url;
}

export function getProjectPublicName(): string {
    return project_public_name;
}

export function getProjectUrl(): string {
    return project_url;
}

export function getPrivacy(): ProjectPrivacy {
    return privacy;
}

export function getProjectFlags(): Array<ProjectFlag> {
    return project_flags;
}

export function getProjectIcon(): string {
    return project_icon;
}
