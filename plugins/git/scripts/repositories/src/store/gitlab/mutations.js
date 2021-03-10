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

export function setAddGitlabRepositoryModal(state, modal) {
    state.add_gitlab_repository_modal = modal;
}
export function setUnlinkGitlabRepositoryModal(state, modal) {
    state.unlink_gitlab_repository_modal = modal;
}
export function setUnlinkGitlabRepository(state, repository) {
    state.unlink_gitlab_repository = repository;
}
export function setEditAccessTokenGitlabRepositoryModal(state, modal) {
    state.edit_access_token_gitlab_repository_modal = modal;
}
export function setEditAccessTokenGitlabRepository(state, repository) {
    state.edit_access_token_gitlab_repository = repository;
}
export function setRegenerateGitlabWebhookModal(state, modal) {
    state.regenerate_gitlab_webhook_modal = modal;
}
export function setRegenerateGitlabWebhookRepository(state, repository) {
    state.regenerate_gitlab_webhook_repository = repository;
}
