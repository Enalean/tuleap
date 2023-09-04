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
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { SaveDescriptionComment } from "../../src/description-comment/PullRequestDescriptionCommentSaver";
import type { DescriptionCommentFormPresenter } from "../../src/description-comment/PullRequestDescriptionCommentFormPresenter";

export type SaveDescriptionCommentStub = SaveDescriptionComment & {
    getLastCallParams: () => DescriptionCommentFormPresenter | undefined;
};

export const SaveDescriptionCommentStub = {
    withResponsePayload: (payload: PullRequest): SaveDescriptionCommentStub => {
        let last_call_params: DescriptionCommentFormPresenter | undefined = undefined;

        return {
            getLastCallParams: () => last_call_params,
            saveDescriptionComment: (
                description: DescriptionCommentFormPresenter,
            ): ResultAsync<PullRequest, Fault> => {
                last_call_params = description;

                return okAsync(payload);
            },
        };
    },
    withFault: (fault: Fault): SaveDescriptionComment => ({
        saveDescriptionComment: (): ResultAsync<PullRequest, Fault> => errAsync(fault),
    }),
    withDefault: (): SaveDescriptionComment => ({
        saveDescriptionComment: (): ResultAsync<PullRequest, Fault> =>
            errAsync(
                Fault.fromMessage(
                    "SaveDescriptionCommentStub::saveDescriptionComment was called while it's not configured",
                ),
            ),
    }),
};
