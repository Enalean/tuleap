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

import type { ControlNewCommentForm } from "../../src/new-comment-form/NewCommentFormController";
import type { NewCommentFormPresenter } from "../../src/new-comment-form/NewCommentFormPresenter";

const noop = (): void => {
    // Do nothing
};

export const ControlNewCommentFormStub = (): ControlNewCommentForm => ({
    buildInitialPresenter: (): NewCommentFormPresenter => {
        return {
            comment_content: "A presenter built by ControlNewCommentFormStub",
            comment_author: {
                avatar_url: "url/to/avatar.png",
            },
            is_cancel_allowed: true,
            is_being_submitted: false,
            is_submittable: true,
        };
    },
    saveNewComment: (): Promise<void> => {
        return Promise.resolve();
    },
    cancelNewComment: noop,
    triggerPostSubmitCallback: noop,
    shouldFocusWritingZoneOnceRendered: () => true,
    getProjectId: () => 105,
});
