/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

export {
    setBreadcrumbSettings,
    getAdministrationUrl,
    getRepositoryListUrl,
    getForkRepositoriesUrl,
    getProjectPublicName,
    getProjectUrl,
    getPrivacy,
    getProjectFlags,
};

let administration_url,
    repository_list_url,
    fork_repositories_url,
    project_public_name,
    project_url,
    privacy,
    project_flags;

function setBreadcrumbSettings(
    admin_url,
    repositories_url,
    fork_url,
    proj_public_name,
    proj_url,
    proj_privacy,
    proj_flags
) {
    administration_url = admin_url;
    repository_list_url = repositories_url;
    fork_repositories_url = fork_url;
    project_public_name = proj_public_name;
    project_url = proj_url;
    privacy = proj_privacy;
    project_flags = proj_flags;
}

function getAdministrationUrl() {
    return administration_url;
}

function getRepositoryListUrl() {
    return repository_list_url;
}

function getForkRepositoriesUrl() {
    return fork_repositories_url;
}

function getProjectPublicName() {
    return project_public_name;
}

function getProjectUrl() {
    return project_url;
}

function getPrivacy() {
    return privacy;
}

function getProjectFlags() {
    return project_flags;
}
