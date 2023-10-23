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

import { describe, beforeEach, expect, it, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { FORMAT_TEXT } from "@tuleap/plugin-pullrequest-constants";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import { PullRequestCommentControllerStub } from "../../tests/stubs/PullRequestCommentControllerStub";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import type { HostElement } from "./PullRequestComment";
import { getCommentFooter } from "./PullRequestCommentFooterTemplate";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import type { ControlPullRequestComment } from "./PullRequestCommentController";

const is_comment_edition_enabled = true;

describe("PullRequestCommentFooterTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    it.each([
        ["an inline comment", PullRequestCommentPresenterStub.buildInlineCommentOutdated(), []],
        ["a global comment", PullRequestCommentPresenterStub.buildGlobalComment(), []],
    ])(`Given %s, Then it should display a footer`, (expectation, comment, replies) => {
        const host = {
            comment,
            replies,
        } as unknown as HostElement;
        const render = getCommentFooter(host, GettextProviderStub);

        render(host, target);

        const footer = selectOrThrow(target, "[data-test=pull-request-comment-footer]");

        expect(footer).not.toBeNull();
    });

    it.each([
        [
            "that the current comment is the root comment and there are some replies",
            PullRequestCommentPresenterStub.buildGlobalComment(),
            [],
        ],
        [
            "that the current comment has reply",
            PullRequestCommentPresenterStub.buildInlineCommentWithData({ id: 10 }),
            [
                PullRequestCommentPresenterStub.buildInlineCommentWithData({ id: 10 }),
                PullRequestCommentPresenterStub.buildInlineCommentWithData({ id: 11 }),
            ],
        ],
    ])(`Given %s, Then it should not display a footer`, (expectation_string, comment, replies) => {
        const host = {
            comment,
            replies,
        } as unknown as HostElement;
        const render = getCommentFooter(host, GettextProviderStub);

        render(host, target);

        const footer = target.querySelector("[data-test=pullrequest-comment-footer]");

        expect(footer).toBeNull();
    });

    it("When the [Reply] button is clicked, then it should show the reply form", () => {
        const controller = PullRequestCommentControllerStub();
        const showReplyForm = vi.spyOn(controller, "showReplyForm");
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            controller,
            replies: [],
        } as unknown as HostElement;

        const render = getCommentFooter(host, GettextProviderStub);

        render(host, target);

        selectOrThrow(target, "[data-test=button-reply-to-comment]").click();

        expect(showReplyForm).toHaveBeenCalledTimes(1);
    });

    describe("Edit button", () => {
        let controller: ControlPullRequestComment;
        const current_user_id = 102;

        beforeEach(() => {
            controller = PullRequestCommentControllerStub(current_user_id);
        });

        const getHost = (comment: PullRequestCommentPresenter): HostElement =>
            ({
                comment,
                controller,
                replies: [],
                is_comment_edition_enabled,
            }) as unknown as HostElement;

        it("When the current user is the author of the comment, then the footer should contain an [Edit] button", () => {
            const host = getHost(
                PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                    user: { id: current_user_id } as User,
                }),
            );
            const render = getCommentFooter(host, GettextProviderStub);
            render(host, target);

            expect(target.querySelector("[data-test=button-edit-comment]")).not.toBeNull();
        });

        it("When the current user is not the author of the comment, then the footer should NOT contain an [Edit] button", () => {
            const host = getHost(
                PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                    user: { id: 200 } as User,
                }),
            );
            const render = getCommentFooter(host, GettextProviderStub);
            render(host, target);

            expect(target.querySelector("[data-test=button-edit-comment]")).toBeNull();
        });

        it("When the comment is in text format, then the footer should NOT contain an [Edit] button", () => {
            const host = getHost(
                PullRequestCommentPresenterStub.buildGlobalCommentWithData({ format: FORMAT_TEXT }),
            );
            const render = getCommentFooter(host, GettextProviderStub);
            render(host, target);

            expect(target.querySelector("[data-test=button-edit-comment]")).toBeNull();
        });

        it("When it is clicked, then it should show the edition form", () => {
            const host = getHost(
                PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                    user: { id: current_user_id } as User,
                }),
            );
            const showEditionForm = vi.spyOn(controller, "showEditionForm");
            const render = getCommentFooter(host, GettextProviderStub);
            render(host, target);

            selectOrThrow(target, "[data-test=button-edit-comment]").click();

            expect(showEditionForm).toHaveBeenCalledOnce();
        });
    });
});
