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

import { okAsync, errAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { SaveComment } from "../../src/new-comment-form/types";
import type { NewCommentFormPresenter } from "../../src/new-comment-form/NewCommentFormPresenter";

export type SaveCommentStub = SaveComment & {
    getLastCallParams: () => NewCommentFormPresenter | undefined;
};

export const SaveCommentStub = {
    withResponsePayload: (payload: PullRequestComment): SaveCommentStub => {
        let last_call_params: NewCommentFormPresenter | undefined = undefined;

        return {
            getLastCallParams: () => last_call_params,
            saveComment: (
                new_comment: NewCommentFormPresenter,
            ): ResultAsync<PullRequestComment, Fault> => {
                last_call_params = new_comment;

                return okAsync(payload);
            },
        };
    },

    withDefault: (): SaveComment => ({
        saveComment: (): ResultAsync<PullRequestComment, Fault> =>
            errAsync(
                Fault.fromMessage(
                    "SaveCommentStub::saveComment was called while it's not configured",
                ),
            ),
    }),

    withFault: (fault: Fault): SaveComment => ({
        saveComment: (): ResultAsync<PullRequestComment, Fault> => errAsync(fault),
    }),
};
