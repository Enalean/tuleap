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
import { selectOrThrow } from "@tuleap/dom";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { FORMAT_TEXT } from "@tuleap/plugin-pullrequest-constants";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import { GettextProviderStub } from "../../../tests/stubs/GettextProviderStub";
import { ControlPullRequestCommentReplyStub } from "../../../tests/stubs/ControlPullRequestCommentReplyStub";
import type { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";
import type { HostElement } from "./PullRequestCommentReply";
import { buildFooterForComment } from "./PullRequestCommentReplyFooterTemplate";

const is_comment_edition_enabled = true;

describe("PullRequestCommentReplyFooterTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    it("When the reply is not the last one in the thread, then no [Reply] button is shown", () => {
        const host = {
            is_last_reply: false,
        } as unknown as HostElement;

        const render = buildFooterForComment(host, GettextProviderStub);

        render(host, target);

        expect(target.querySelector("[data-test=button-reply-to-comment]")).toBeNull();
    });

    it("When the [Reply] button is clicked, then it should show the reply form", () => {
        const controller = ControlPullRequestCommentReplyStub();
        const host = {
            controller,
            is_last_reply: true,
        } as unknown as HostElement;

        const showReplyForm = vi.spyOn(controller, "showReplyForm");
        const render = buildFooterForComment(host, GettextProviderStub);

        render(host, target);

        selectOrThrow(target, "[data-test=button-reply-to-comment]").click();

        expect(showReplyForm).toHaveBeenCalledTimes(1);
    });

    describe("Edit button", () => {
        const current_user_id = 102;
        const getHost = (comment: PullRequestCommentPresenter): HostElement =>
            ({
                comment,
                controller: ControlPullRequestCommentReplyStub(current_user_id),
                replies: [],
                is_comment_edition_enabled,
            }) as unknown as HostElement;

        it("When the current user is the author of the comment, then the footer should contain an [Edit] button", () => {
            const host = getHost(
                PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                    user: { id: current_user_id } as User,
                }),
            );
            const render = buildFooterForComment(host, GettextProviderStub);
            render(host, target);

            expect(target.querySelector("[data-test=button-edit-comment]")).not.toBeNull();
        });

        it("When the current user is not the author of the comment, then the footer should NOT contain an [Edit] button", () => {
            const host = getHost(
                PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                    user: { id: 200 } as User,
                }),
            );
            const render = buildFooterForComment(host, GettextProviderStub);
            render(host, target);

            expect(target.querySelector("[data-test=button-edit-comment]")).toBeNull();
        });

        it("When the comment is in text format, then the footer should NOT contain an [Edit] button", () => {
            const host = getHost(
                PullRequestCommentPresenterStub.buildGlobalCommentWithData({ format: FORMAT_TEXT }),
            );
            const render = buildFooterForComment(host, GettextProviderStub);
            render(host, target);

            expect(target.querySelector("[data-test=button-edit-comment]")).toBeNull();
        });
    });
});
