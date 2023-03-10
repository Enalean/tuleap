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

import { describe, beforeEach, expect, it } from "vitest";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import type { HostElement } from "./PullRequestComment";
import { getCommentFooter } from "./PullRequestCommentFooterTemplate";
import { selectOrThrow } from "@tuleap/dom";
import { PullRequestCommentControllerStub } from "../../tests/stubs/PullRequestCommentControllerStub";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";

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
        [
            "the latest reply of a comment",
            PullRequestCommentPresenterStub.buildInlineCommentWithData({ id: 11 }),
            [
                PullRequestCommentPresenterStub.buildInlineCommentWithData({ id: 10 }),
                PullRequestCommentPresenterStub.buildInlineCommentWithData({ id: 11 }),
            ],
        ],
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
            "a pull-request event comment",
            PullRequestCommentPresenterStub.buildPullRequestEventComment(),
            [],
        ],
        [
            "that the current comment is the root comment and there are some replies",
            PullRequestCommentPresenterStub.buildGlobalComment(),
            [],
        ],
        [
            "that the current comment is not the latest reply",
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
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            controller,
            replies: [],
        } as unknown as HostElement;

        const render = getCommentFooter(host, GettextProviderStub);

        render(host, target);

        selectOrThrow(target, "[data-test=button-reply-to-comment]").click();

        expect(controller.showReplyForm).toHaveBeenCalledTimes(1);
    });
});
