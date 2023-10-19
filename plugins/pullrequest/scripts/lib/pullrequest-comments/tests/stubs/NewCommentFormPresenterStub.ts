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

import type { NewCommentFormPresenter } from "../../src/new-comment-form/NewCommentFormPresenter";
import { CurrentPullRequestUserPresenterStub } from "./CurrentPullRequestUserPresenterStub";

const presenter_base = {
    comment_author: CurrentPullRequestUserPresenterStub.withDefault(),
};

export const NewCommentFormPresenterStub = {
    buildEmpty: (): NewCommentFormPresenter => ({
        ...presenter_base,
        comment_content: "",
        is_being_submitted: false,
        is_submittable: false,
        is_cancel_allowed: true,
    }),
    buildWithContent: (content: string): NewCommentFormPresenter => ({
        ...presenter_base,
        comment_content: content,
        is_being_submitted: false,
        is_submittable: true,
        is_cancel_allowed: true,
    }),
    buildBeingSubmitted: (content: string): NewCommentFormPresenter => ({
        ...presenter_base,
        comment_content: content,
        is_being_submitted: true,
        is_submittable: true,
        is_cancel_allowed: true,
    }),
};
