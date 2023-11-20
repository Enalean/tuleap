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
import type { EditedComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { SaveEditedComment } from "../../src/comment/edition/EditedCommentSaver";

export const SaveEditedCommentStub = {
    withSuccessPayload: (payload: EditedComment): SaveEditedComment => ({
        saveEditedComment: (): ResultAsync<EditedComment, Fault> => {
            return okAsync(payload);
        },
    }),
    withFault: (fault: Fault): SaveEditedComment => ({
        saveEditedComment: (): ResultAsync<EditedComment, Fault> => {
            return errAsync(fault);
        },
    }),
    withDefault: (): SaveEditedComment => ({
        saveEditedComment: (): ResultAsync<EditedComment, Fault> => {
            return errAsync(
                Fault.fromMessage(
                    "SaveEditedCommentStub::saveEditedComment called while it wasn't expected",
                ),
            );
        },
    }),
};
