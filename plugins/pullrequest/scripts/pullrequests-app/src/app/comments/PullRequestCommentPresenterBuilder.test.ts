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

import type { AngularUIRouterState } from "../types";
import {
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
    TYPE_EVENT_PULLREQUEST_ACTION,
    EVENT_TYPE_UPDATE,
    INLINE_COMMENT_POSITION_RIGHT,
} from "@tuleap/plugin-pullrequest-constants";
import type {
    ActionOnPullRequestEvent,
    CommentOnFile,
    GlobalComment,
    PullRequest,
    User,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import { PullRequestCommentPresenterBuilder } from "./PullRequestCommentPresenterBuilder";
import { setCatalog } from "../gettext-catalog";
import { AngularUIRouterStateStub } from "../../../tests/stubs/AngularUIRouterStateStub";

const user: User = {
    id: 102,
    user_url: "url/to/user_profile.html",
    avatar_url: "url/to/user_avatar.png",
    display_name: "Joe l'Asticot",
};

describe("PullRequestCommentPresenterBuilder", () => {
    let $state: AngularUIRouterState;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid, getPlural: (nb, msgid) => msgid });

        $state = AngularUIRouterStateStub.withHref("url/to/file");
    });

    it("Builds a presenter from a timeline item of type TYPE_GLOBAL_COMMENT", () => {
        const timeline_item: GlobalComment = {
            id: 12,
            post_date: "2020/07/13 16:16",
            content: "my comment\nwith line return",
            type: TYPE_GLOBAL_COMMENT,
            color: "red-wine",
            parent_id: 0,
            user,
        };
        const pullRequest = { id: 1 } as PullRequest;
        const result = PullRequestCommentPresenterBuilder.fromTimelineItem(
            $state,
            timeline_item,
            pullRequest
        );

        expect(result.content).toBe("my comment<br/>with line return");
        expect(result.post_date).toBe("2020/07/13 16:16");
    });

    it("Builds a presenter from a timeline item of type TYPE_INLINE_COMMENT", () => {
        const timeline_item: CommentOnFile = {
            id: 12,
            post_date: "2020/07/13 16:16",
            content: "my comment\nwith line return",
            file_path: "README.md",
            unidiff_offset: 8,
            position: INLINE_COMMENT_POSITION_RIGHT,
            type: TYPE_INLINE_COMMENT,
            is_outdated: false,
            user,
            parent_id: 0,
            color: "waffle-blue",
        };
        const pullRequest = { id: 1 } as PullRequest;
        const result = PullRequestCommentPresenterBuilder.fromTimelineItem(
            $state,
            timeline_item,
            pullRequest
        );

        if (result.type !== TYPE_INLINE_COMMENT) {
            throw new Error("Expected a PullRequestInlineCommentPresenter");
        }

        expect(result.content).toBe("my comment<br/>with line return");
        expect(result.post_date).toBe("2020/07/13 16:16");
        expect(result.file).toStrictEqual({
            file_url: "url/to/file",
            file_path: "README.md",
            unidiff_offset: 8,
            position: INLINE_COMMENT_POSITION_RIGHT,
            is_displayed: true,
        });
    });

    it("Builds a presenter from a timeline item of type TYPE_EVENT_PULLREQUEST_ACTION", () => {
        const timeline_item: ActionOnPullRequestEvent = {
            post_date: "2020/07/13 16:16",
            type: TYPE_EVENT_PULLREQUEST_ACTION,
            event_type: EVENT_TYPE_UPDATE,
            user,
        };
        const pullRequest = { id: 1 } as PullRequest;
        const result = PullRequestCommentPresenterBuilder.fromTimelineItem(
            $state,
            timeline_item,
            pullRequest
        );

        expect(result.content).toBe("Has updated the pull request.");
        expect(result.post_date).toBe("2020/07/13 16:16");
    });

    it("Builds a presenter from a file-diff comment payload", () => {
        const file_diff_comment: CommentOnFile = {
            id: 12,
            post_date: "2020/07/13 16:16",
            content: "my comment",
            user,
            file_path: "README.md",
            unidiff_offset: 8,
            position: INLINE_COMMENT_POSITION_RIGHT,
            parent_id: 0,
            color: "graffiti-yellow",
            type: TYPE_INLINE_COMMENT,
            is_outdated: false,
        };
        const result = PullRequestCommentPresenterBuilder.fromFileDiffComment(file_diff_comment);
        expect(result.type).toBe(TYPE_INLINE_COMMENT);
        expect(result.is_outdated).toBe(false);
        expect(result.file).toStrictEqual({
            file_url: "",
            position: file_diff_comment.position,
            file_path: file_diff_comment.file_path,
            unidiff_offset: file_diff_comment.unidiff_offset,
            is_displayed: false,
        });
    });
});
