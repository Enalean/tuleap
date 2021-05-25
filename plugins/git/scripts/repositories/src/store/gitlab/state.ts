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

import type { GitLabRepository } from "../../type";
import type { Modal } from "tlp";

export interface GitlabState {
    add_gitlab_repository_modal: Modal | null;
    unlink_gitlab_repository_modal: Modal | null;
    unlink_gitlab_repository: GitLabRepository | null;
    edit_access_token_gitlab_repository_modal: Modal | null;
    edit_access_token_gitlab_repository: GitLabRepository | null;
    regenerate_gitlab_webhook_modal: Modal | null;
    regenerate_gitlab_webhook_repository: GitLabRepository | null;
    artifact_closure_modal: Modal | null;
    artifact_closure_repository: GitLabRepository | null;
}

const state: GitlabState = {
    add_gitlab_repository_modal: null,
    unlink_gitlab_repository_modal: null,
    unlink_gitlab_repository: null,
    edit_access_token_gitlab_repository_modal: null,
    edit_access_token_gitlab_repository: null,
    regenerate_gitlab_webhook_modal: null,
    regenerate_gitlab_webhook_repository: null,
    artifact_closure_modal: null,
    artifact_closure_repository: null,
};

export default state;
