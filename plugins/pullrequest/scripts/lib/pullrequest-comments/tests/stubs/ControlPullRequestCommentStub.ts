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

import type { ControlPullRequestComment } from "../../src/comment/PullRequestCommentController";
import { RelativeDateHelperStub } from "./RelativeDateHelperStub";
import { ControlPullRequestCommentReplyStub } from "./ControlPullRequestCommentReplyStub";
import { ControlNewCommentFormStub } from "./ControlNewCommentFormStub";

const noop = (): void => {
    // Do nothing
};

export const ControlPullRequestCommentStub = (
    current_user_id = 102,
): ControlPullRequestComment => ({
    hideReplyForm: noop,
    showReplyForm: noop,
    hideEditionForm: noop,
    showEditionForm: noop,
    displayReplies: noop,
    buildReplyCreationController: () => ControlNewCommentFormStub(),
    getRelativeDateHelper: () => RelativeDateHelperStub,
    getProjectId: () => 105,
    getCurrentUserId: () => current_user_id,
    buildReplyController: () => ControlPullRequestCommentReplyStub(current_user_id),
});
