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
import { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { CommentOnFile } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { SaveNewInlineComment } from "../../src/app/comments/new-comment-form/NewInlineCommentSaver";

export type SaveNewInlineCommentStub = SaveNewInlineComment & {
    getLastCallParams: () => string | undefined;
};

export const SaveNewInlineCommentStub = {
    withResponsePayload: (payload: CommentOnFile): SaveNewInlineCommentStub => {
        let last_call_params: string | undefined = undefined;

        return {
            getLastCallParams: () => last_call_params,
            postComment: (content: string): ResultAsync<CommentOnFile, Fault> => {
                last_call_params = content;

                return okAsync(payload);
            },
        };
    },
    withDefault: (): SaveNewInlineComment => ({
        postComment: (): ResultAsync<CommentOnFile, Fault> => {
            return errAsync(
                Fault.fromMessage(
                    "SaveNewInlineCommentStub::postComment was called while it's not configured"
                )
            );
        },
    }),
};
