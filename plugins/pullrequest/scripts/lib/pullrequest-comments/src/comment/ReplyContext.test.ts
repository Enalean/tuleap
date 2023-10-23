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

import { describe, it, expect } from "vitest";
import { TYPE_GLOBAL_COMMENT, TYPE_INLINE_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import { CurrentPullRequestPresenterStub } from "../../tests/stubs/CurrentPullRequestPresenterStub";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import type {
    ReplyCreationContext,
    ReplyToCommentOnFileContext,
    ReplyToGlobalCommentContext,
} from "../new-comment-form/types";
import { ReplyContext } from "./ReplyContext";

const isReplyToGlobalCommentContext = (
    context: ReplyCreationContext,
): context is ReplyToGlobalCommentContext => {
    return (
        context.type === TYPE_GLOBAL_COMMENT && context.root_comment.type === TYPE_GLOBAL_COMMENT
    );
};

const isReplyToCommentOnFileContext = (
    context: ReplyCreationContext,
): context is ReplyToCommentOnFileContext => {
    return (
        context.type === TYPE_INLINE_COMMENT && context.root_comment.type === TYPE_INLINE_COMMENT
    );
};

const current_user_id = 110;
const current_pull_request_id = 2;

describe("ReplyContext", () => {
    it("Given a global comment, then it should return a ReplyToGlobalCommentContext", () => {
        const context = ReplyContext.fromComment(
            PullRequestCommentPresenterStub.buildGlobalComment(),
            CurrentPullRequestUserPresenterStub.withUserId(current_user_id),
            CurrentPullRequestPresenterStub.withPullRequestId(current_pull_request_id),
        );

        expect(isReplyToGlobalCommentContext(context)).toBe(true);
        expect(context.user_id).toBe(current_user_id);
        expect(context.pull_request_id).toBe(current_pull_request_id);
    });

    it("Given a global comment, then it should return a ReplyToCommentOnFileContext", () => {
        const context = ReplyContext.fromComment(
            PullRequestCommentPresenterStub.buildInlineComment(),
            CurrentPullRequestUserPresenterStub.withUserId(current_user_id),
            CurrentPullRequestPresenterStub.withPullRequestId(current_pull_request_id),
        );

        expect(isReplyToCommentOnFileContext(context)).toBe(true);
        expect(context.user_id).toBe(current_user_id);
        expect(context.pull_request_id).toBe(current_pull_request_id);
    });
});
