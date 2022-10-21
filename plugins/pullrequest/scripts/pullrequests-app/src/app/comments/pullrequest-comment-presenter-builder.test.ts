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

import { PullRequestCommentPresenterBuilder } from "./pullrequest-comment-presenter-builder";
import type {
    FileDiffCommentPayload,
    PullRequestData,
    TimelineEventPayload,
    State,
    AngularMomentType,
} from "./pullrequest-comment-presenter-builder";
import type { PullRequestUser } from "./PullRequestComment";
import { setCatalog } from "../gettext-catalog";

describe("pullRequestCommentPresenterBuilder", () => {
    const $state: State = { href: jest.fn() },
        moment: AngularMomentType = () => ({
            fromNow: () => "1 second ago",
        });

    beforeEach(() => {
        setCatalog({ getString: (msgid: string) => msgid });
    });

    it("Builds a presenter from timeline payload for comment", () => {
        const builder = PullRequestCommentPresenterBuilder($state, moment);
        const event: TimelineEventPayload = {
            post_date: "2022-10-21 11:59:00",
            file_url: "my_url",
            content: "my comment\nwith line return",
            file_path: "",
            type: "comment",
            is_outdated: false,
            user: {} as PullRequestUser,
        } as TimelineEventPayload;
        const pullRequest: PullRequestData = { id: 1 };
        const result = builder.fromTimelineEvent(event, pullRequest);

        expect(result.content).toBe("my comment<br/>with line return");
        expect(result.is_inline_comment).toBe(false);
        expect(result.post_date).toBe("1 second ago");
    });

    it("Builds a presenter from timeline payload for inline comments", () => {
        const builder = PullRequestCommentPresenterBuilder($state, moment);
        const event: TimelineEventPayload = {
            post_date: "2022-10-21 11:59:00",
            file_url: "my_url",
            content: "my comment\nwith line return",
            file_path: "",
            type: "inline-comment",
            is_outdated: false,
            user: {} as PullRequestUser,
        } as TimelineEventPayload;
        const pullRequest: PullRequestData = { id: 1 };
        const result = builder.fromTimelineEvent(event, pullRequest);

        expect(result.content).toBe("my comment<br/>with line return");
        expect(result.is_inline_comment).toBe(true);
        expect(result.post_date).toBe("1 second ago");
    });

    it("Builds a presenter from timeline payload for timeline events", () => {
        const builder = PullRequestCommentPresenterBuilder($state, moment);
        const event: TimelineEventPayload = {
            post_date: "2022-10-21 11:59:00",
            file_url: "my_url",
            content: "",
            file_path: "",
            type: "timeline-event",
            is_outdated: false,
            event_type: "update",
            user: {} as PullRequestUser,
        } as TimelineEventPayload;
        const pullRequest: PullRequestData = { id: 1 };
        const result = builder.fromTimelineEvent(event, pullRequest);

        expect(result.content).toBe("Has updated the pull request.");
        expect(result.is_inline_comment).toBe(false);
        expect(result.post_date).toBe("1 second ago");
    });

    it("Builds a presenter from comment payload", () => {
        const builder = PullRequestCommentPresenterBuilder($state, moment);

        const event: FileDiffCommentPayload = {
            post_date: "2022-10-21 11:59:00",
            content: "my comment",
            user: {} as PullRequestUser,
        };
        const result = builder.fromFileDiffComment(event);
        expect(result.type).toBe("inline-comment");
        expect(result.is_outdated).toBe(false);
        expect(result.is_inline_comment).toBe(true);
    });
});
