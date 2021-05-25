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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */
import type { GitLabRepository, FormattedGitLabRepository } from "../type";

export function formatRepository(repository: GitLabRepository): FormattedGitLabRepository {
    const label = extractLabelFromName(repository.name);

    return {
        id: "gitlab_" + repository.id,
        integration_id: repository.id,
        description: repository.description,
        label: label,
        last_update_date: repository.last_push_date,
        normalized_path: repository.name,
        additional_information: [],
        path_without_project: extractPathWithoutProject(repository.name, label),
        gitlab_data: {
            gitlab_repository_url: repository.gitlab_repository_url,
            gitlab_repository_id: repository.gitlab_repository_id,
            is_webhook_configured: repository.is_webhook_configured,
        },
        allow_artifact_closure: repository.allow_artifact_closure,
    };
}

function extractLabelFromName(repository_name: string): string {
    if (repository_name.lastIndexOf("/") === -1) {
        return repository_name;
    }

    return repository_name.substr(repository_name.lastIndexOf("/") + 1);
}

function extractPathWithoutProject(repository_name: string, label: string): string {
    if (repository_name === label) {
        return "";
    }

    return repository_name.replace("/" + label, "");
}
