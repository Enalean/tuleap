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
import type { Repository, FormattedGitLabRepository } from "../type";

export function isGitlabRepository(repository: FormattedGitLabRepository | Repository): boolean {
    return (
        Object.prototype.hasOwnProperty.call(repository, "gitlab_data") &&
        repository.gitlab_data !== null &&
        repository.gitlab_data !== undefined &&
        Object.prototype.hasOwnProperty.call(repository.gitlab_data, "gitlab_repository_url") &&
        Object.prototype.hasOwnProperty.call(repository.gitlab_data, "gitlab_repository_id")
    );
}

export function isGitlabRepositoryWellConfigured(
    repository: FormattedGitLabRepository | Repository,
): boolean {
    return (
        Object.prototype.hasOwnProperty.call(repository, "gitlab_data") &&
        repository.gitlab_data !== null &&
        repository.gitlab_data !== undefined &&
        Object.prototype.hasOwnProperty.call(repository.gitlab_data, "gitlab_repository_url") &&
        Object.prototype.hasOwnProperty.call(repository.gitlab_data, "gitlab_repository_id") &&
        Object.prototype.hasOwnProperty.call(repository.gitlab_data, "is_webhook_configured") &&
        repository.gitlab_data.is_webhook_configured
    );
}
