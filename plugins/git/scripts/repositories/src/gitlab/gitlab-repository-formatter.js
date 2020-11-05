/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export function formatRepository(repository) {
    let repository_formatted = {};

    repository_formatted.id = "gitlab_" + repository.id;
    repository_formatted.integration_id = repository.id;
    repository_formatted.description = repository.description;
    repository_formatted.label = repository.name;
    repository_formatted.last_update_date = repository.last_push_date;
    repository_formatted.normalized_path = repository.path;
    repository_formatted.additional_information = [];
    repository_formatted.path_without_project = repository.path.replace("/" + repository.name, "");
    repository_formatted.gitlab_data = {
        full_url: repository.full_url,
        gitlab_id: repository.gitlab_id,
    };

    return repository_formatted;
}
