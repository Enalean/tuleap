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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { CurrentPullRequestUserPresenterStub } from "../../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { CurrentPullRequestPresenterStub } from "../../../tests/stubs/CurrentPullRequestPresenterStub";
import type { HostElement } from "./PullRequestCommentReply";
import { PullRequestCommentReplyController } from "./PullRequestCommentReplyController";

describe("PullRequestCommentReplyController", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("showReplyForm() should dispatch a show-reply-form event", () => {
        const host = doc.createElement("div") as unknown as HostElement;
        const dispatchEvent = vi.spyOn(host, "dispatchEvent");

        PullRequestCommentReplyController(
            CurrentPullRequestUserPresenterStub.withDefault(),
            CurrentPullRequestPresenterStub.withDefault(),
        ).showReplyForm(host);

        expect(dispatchEvent).toHaveBeenCalledOnce();
        expect(dispatchEvent.mock.calls[0][0].type).toBe("show-reply-form");
    });

    it("hideReplyForm() should dispatch a hide-reply-form event", () => {
        const host = doc.createElement("div") as unknown as HostElement;
        const dispatchEvent = vi.spyOn(host, "dispatchEvent");

        PullRequestCommentReplyController(
            CurrentPullRequestUserPresenterStub.withDefault(),
            CurrentPullRequestPresenterStub.withDefault(),
        ).hideReplyForm(host);

        expect(dispatchEvent).toHaveBeenCalledOnce();
        expect(dispatchEvent.mock.calls[0][0].type).toBe("hide-reply-form");
    });

    it("getCurrentUserId() should return the current user id", () => {
        const user_id = 140;
        expect(
            PullRequestCommentReplyController(
                CurrentPullRequestUserPresenterStub.withUserId(user_id),
                CurrentPullRequestPresenterStub.withDefault(),
            ).getCurrentUserId(),
        ).toBe(user_id);
    });

    it("getProjectId() should return the current project id", () => {
        const project_id = 140;
        expect(
            PullRequestCommentReplyController(
                CurrentPullRequestUserPresenterStub.withDefault(),
                CurrentPullRequestPresenterStub.withProjectId(project_id),
            ).getProjectId(),
        ).toBe(project_id);
    });
});
