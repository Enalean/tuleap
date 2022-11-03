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

import { setCatalog } from "../gettext-catalog";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import type { HostElement } from "./PullRequestComment";
import { getReplyFormTemplate } from "./PullRequestCommentReplyFormTemplate";
import { selectOrThrow } from "@tuleap/dom";
import { PullRequestCommentControllerStub } from "../../../tests/stubs/PullRequestCommentControllerStub";
import { CurrentPullRequestUserPresenterStub } from "../../../tests/stubs/CurrentPullRequestUserPresenterStub";

describe("PullRequestCommentReplyFormTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    it(`Should render the comment reply form when it is toggled`, () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildPullRequestEventComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            is_reply_form_displayed: true,
        } as unknown as HostElement;
        const render = getReplyFormTemplate(host);
        render(host, target);

        expect(target.querySelector("[data-test=pull-request-comment-reply-form]")).not.toBeNull();
    });

    it(`Should NOT render the comment reply form when it is NOT toggled`, () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildPullRequestEventComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            is_reply_form_displayed: false,
        } as unknown as HostElement;
        const render = getReplyFormTemplate(host);
        render(host, target);

        expect(target.querySelector("[data-test=pull-request-comment-reply-form]")).toBeNull();
    });

    it("Should hide the form when the [Cancel] button is clicked", () => {
        const controller = PullRequestCommentControllerStub();
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            is_reply_form_displayed: true,
            controller,
        } as unknown as HostElement;

        const render = getReplyFormTemplate(host);

        render(host, target);

        selectOrThrow(target, "[data-test=button-cancel-reply]").click();

        expect(controller.hideReplyForm).toHaveBeenCalledTimes(1);
    });
});
