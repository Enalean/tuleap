/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import type { Fault } from "@tuleap/fault";
import type { RetrieveComments } from "../../src/domain/comments/RetrieveComments";
import type { FollowUpComment } from "../../src/domain/comments/FollowUpComment";

export const RetrieveCommentsStub = {
    withoutComments: (): RetrieveComments => ({
        getComments: () => okAsync([]),
    }),

    withComments: (
        first_comment: FollowUpComment,
        ...other_comments: readonly FollowUpComment[]
    ): RetrieveComments => ({
        getComments: () => okAsync([first_comment, ...other_comments]),
    }),

    withFault: (fault: Fault): RetrieveComments => ({
        getComments: () => errAsync(fault),
    }),
};
