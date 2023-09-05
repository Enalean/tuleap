/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import type { GitlabState } from "./state";
import type { GitLabRepository } from "../../type";
import type { Modal } from "tlp";

export function setAddGitlabRepositoryModal(state: GitlabState, modal: Modal): void {
    state.add_gitlab_repository_modal = modal;
}
export function setUnlinkGitlabRepositoryModal(state: GitlabState, modal: Modal): void {
    state.unlink_gitlab_repository_modal = modal;
}
export function setUnlinkGitlabRepository(state: GitlabState, repository: GitLabRepository): void {
    state.unlink_gitlab_repository = repository;
}
export function setEditAccessTokenGitlabRepositoryModal(state: GitlabState, modal: Modal): void {
    state.edit_access_token_gitlab_repository_modal = modal;
}
export function setEditAccessTokenGitlabRepository(
    state: GitlabState,
    repository: GitLabRepository,
): void {
    state.edit_access_token_gitlab_repository = repository;
}
export function setRegenerateGitlabWebhookModal(state: GitlabState, modal: Modal): void {
    state.regenerate_gitlab_webhook_modal = modal;
}
export function setRegenerateGitlabWebhookRepository(
    state: GitlabState,
    repository: GitLabRepository,
): void {
    state.regenerate_gitlab_webhook_repository = repository;
}

export function setArtifactClosureModal(state: GitlabState, modal: Modal): void {
    state.artifact_closure_modal = modal;
}

export function setArtifactClosureRepository(
    state: GitlabState,
    repository: GitLabRepository,
): void {
    state.artifact_closure_repository = repository;
}

export function setCreateBranchPrefixModal(state: GitlabState, modal: Modal): void {
    state.create_branch_prefix_modal = modal;
}

export function setCreateBranchPrefixRepository(
    state: GitlabState,
    repository: GitLabRepository,
): void {
    state.create_branch_prefix_repository = repository;
}

export function showAddGitlabRepositoryModal(state: GitlabState): void {
    if (!state.add_gitlab_repository_modal) {
        return;
    }
    state.add_gitlab_repository_modal.toggle();
}
