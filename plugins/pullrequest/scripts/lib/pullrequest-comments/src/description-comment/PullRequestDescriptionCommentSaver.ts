/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import { patchJSON, uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { DescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import { getContentFormat } from "../helpers/content-format";

export interface SaveDescriptionComment {
    saveDescriptionComment: (
        description: DescriptionCommentFormPresenter,
        is_comments_markdown_mode_enabled: boolean
    ) => ResultAsync<PullRequest, Fault>;
}

export const PullRequestDescriptionCommentSaver = (): SaveDescriptionComment => ({
    saveDescriptionComment: (
        description: DescriptionCommentFormPresenter,
        is_comments_markdown_mode_enabled: boolean
    ): ResultAsync<PullRequest, Fault> => {
        return patchJSON<PullRequest>(uri`/api/v1/pull_requests/${description.pull_request_id}`, {
            description: description.description_content,
            description_format: getContentFormat(is_comments_markdown_mode_enabled),
        });
    },
});
