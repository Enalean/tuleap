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
import { ControlPullRequestCommentStub } from "../../tests/stubs/ControlPullRequestCommentStub";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import type { HostElement } from "./PullRequestComment";
import { getCommentFooter } from "./PullRequestCommentFooterTemplate";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import type { ControlPullRequestComment } from "./PullRequestCommentController";
import type { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";

const current_user_id = 102;

describe("PullRequestCommentFooterTemplate", () => {
    let target: ShadowRoot,
        controller: ControlPullRequestComment,
        replies: PullRequestCommentRepliesCollectionPresenter;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        controller = ControlPullRequestCommentStub(current_user_id);
        replies = [];
    });

    const render = (comment: PullRequestCommentPresenter): void => {
        const host = { comment, replies, controller } as HostElement;
        const updateFunction = getCommentFooter(host, GettextProviderStub);
        updateFunction(host, target);
    };

    it.each([
        ["an inline comment", PullRequestCommentPresenterStub.buildInlineCommentOutdated()],
        ["a global comment", PullRequestCommentPresenterStub.buildGlobalComment()],
    ])(`Given %s, Then it should display a footer`, (expectation, comment) => {
        render(comment);

        const footer = selectOrThrow(target, "[data-test=pull-request-comment-footer]");

        expect(footer).not.toBeNull();
    });

    it(`When there is neither a [Reply] nor [Edit] button, Then it should not display a footer`, () => {
        const comment = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
            id: 35,
            format: FORMAT_TEXT,
        });
        replies = [PullRequestCommentPresenterStub.buildGlobalCommentWithData({ id: 37 })];

        render(comment);

        const footer = target.querySelector("[data-test=pull-request-comment-footer]");

        expect(footer).toBeNull();
    });

    it("When the [Reply] button is clicked, then it should show the reply form", () => {
        const showReplyForm = vi.spyOn(controller, "showReplyForm");

        render(PullRequestCommentPresenterStub.buildGlobalComment());

        selectOrThrow(target, "[data-test=button-reply-to-comment]").click();

        expect(showReplyForm).toHaveBeenCalledTimes(1);
    });

    describe("Edit button", () => {
        it("When the current user is the author of the comment, then the footer should contain an [Edit] button", () => {
            const comment = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                user: { id: current_user_id } as User,
            });
            render(comment);

            expect(target.querySelector("[data-test=button-edit-comment]")).not.toBeNull();
        });

        it("When the current user is not the author of the comment, then the footer should NOT contain an [Edit] button", () => {
            const comment = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                user: { id: 200 } as User,
            });
            render(comment);

            expect(target.querySelector("[data-test=button-edit-comment]")).toBeNull();
        });

        it("When the comment is in text format, then the footer should NOT contain an [Edit] button", () => {
            const comment = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                format: FORMAT_TEXT,
            });
            render(comment);

            expect(target.querySelector("[data-test=button-edit-comment]")).toBeNull();
        });

        it("When it is clicked, then it should show the edition form", () => {
            const comment = PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                user: { id: current_user_id } as User,
            });
            const showEditionForm = vi.spyOn(controller, "showEditionForm");
            render(comment);

            selectOrThrow(target, "[data-test=button-edit-comment]").click();

            expect(showEditionForm).toHaveBeenCalledOnce();
        });
    });
});
