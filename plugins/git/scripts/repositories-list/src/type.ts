/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import type { Modal } from "@tuleap/tlp-modal";

export interface State {
    repositories_for_owner: RepositoriesForOwner;
    filter: string;
    selected_owner_id: string | number;
    error_message_type: number;
    success_message: string;
    is_loading_initial: boolean;
    is_loading_next: boolean;
    add_repository_modal: null | Modal;
    display_mode: string;
    is_first_load_done: boolean;
    services_name_used: string[];
    add_gitlab_repository_modal: null | Modal;
    unlink_gitlab_repository_modal: null | Modal;
    unlink_gitlab_repository: null | Repository;
}

export type RepositoriesForOwner = {
    [key in number | string]: Array<Repository | FormattedGitLabRepository | Folder>;
};

export interface Repository {
    id: string | number;
    integration_id: string | number;
    description: string;
    label: string;
    last_update_date: string;
    last_push_date: string;
    additional_information: RepositoryAdditionalInformation;
    normalized_path?: string;
    path_without_project: string;
    uri: string;
    name: string;
    path: string;
    permissions: {
        read: PermissionsRepository[];
        write: PermissionsRepository[];
        rewind: PermissionsRepository[];
    };
    server: null | {
        id: number;
        html_url: string;
    };
    html_url: string;
    gitlab_data?: null | GitLabData;
    allow_artifact_closure: boolean;
    create_branch_prefix: string;
}

export interface RepositoryAdditionalInformation {
    opened_pull_requests: string;
}

export interface FormattedGitLabRepository {
    id: string | number;
    integration_id: string | number;
    description: string;
    label: string;
    last_update_date: string;
    additional_information: [];
    normalized_path?: string;
    path_without_project: string;
    gitlab_data?: null | GitLabData;
    allow_artifact_closure: boolean;
    create_branch_prefix: string;
}

export interface GitlabDataWithPath {
    normalized_path: string;
    gitlab_data: GitLabData;
}

export interface PermissionsRepository {
    id: string;
    uri: string;
    label: string;
    users_uri: string;
    short_name: string;
    key: string;
}

export interface GitLabData {
    gitlab_repository_url: string;
    gitlab_repository_id: number;
    is_webhook_configured: boolean;
}

export interface GitLabDataWithToken {
    gitlab_api_token: string;
}

export interface GitLabDataWithTokenPayload {
    gitlab_integration_id: number | string;
    gitlab_api_token: string;
}

export interface Folder {
    is_folder: boolean;
    label: string;
    children: Array<Folder | Repository | FormattedGitLabRepository>;
    normalized_path?: string;
    path_without_project?: string;
}

export interface GitLabCredentials {
    token: string;
    server_url: string;
}

export interface GitLabCredentialsWithProjects extends GitLabCredentials {
    projects: GitlabProject[];
}

export interface GitLabRepository {
    description: string;
    gitlab_repository_url: string;
    gitlab_repository_id: number;
    id: number;
    last_push_date: string;
    name: string;
    is_webhook_configured: boolean;
    allow_artifact_closure: boolean;
    create_branch_prefix: string;
}

export interface RepositoryOwner {
    id: number;
    display_name: string;
}

export interface ExternalPlugins {
    plugin_name: string;
    data: Array<JenkinsServer>;
}

export interface GitlabProject {
    id: number;
    name: string;
    path_with_namespace: string;
    name_with_namespace: string;
    avatar_url: string;
    web_url: string;
}

export interface JenkinsServer {
    readonly id: number;
    readonly url: string;
}
