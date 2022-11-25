/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { ReplyCommentFormPresenter } from "../../src/app/comments/ReplyCommentFormPresenter";

const presenter_base = {
    parent_comment_id: 12,
    pull_request_id: 144,
    comment_author: {
        user_id: 104,
        avatar_url: "url/to/avatar.png",
    },
};
export const ReplyCommentFormPresenterStub = {
    buildEmpty: (): ReplyCommentFormPresenter => ({
        ...presenter_base,
        comment_content: "",
        is_being_submitted: false,
        is_submittable: false,
    }),
    buildWithContent: (content: string): ReplyCommentFormPresenter => ({
        ...presenter_base,
        comment_content: content,
        is_being_submitted: false,
        is_submittable: true,
    }),
    buildBeingSubmitted: (content: string): ReplyCommentFormPresenter => ({
        ...presenter_base,
        comment_content: content,
        is_being_submitted: true,
        is_submittable: true,
    }),
};
