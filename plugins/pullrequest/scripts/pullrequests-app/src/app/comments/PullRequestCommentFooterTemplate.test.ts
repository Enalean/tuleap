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

import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import type { HostElement } from "./PullRequestComment";
import { getCommentFooter } from "./PullRequestCommentFooterTemplate";
import { selectOrThrow } from "@tuleap/dom";
import { setCatalog } from "../gettext-catalog";

describe("PullRequestCommentFooterTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    it.each([
        ["an inline comment", PullRequestCommentPresenterStub.buildInlineCommentOutdated()],
        ["a global comment", PullRequestCommentPresenterStub.buildGlobalComment()],
    ])(`Given %s, Then it should display a footer`, (expectation, comment) => {
        const host = { comment } as unknown as HostElement;
        const render = getCommentFooter(host);

        render(host, target);

        const footer = selectOrThrow(target, "[data-test=pull-request-comment-footer]");

        expect(footer).not.toBeNull();
    });

    it(`Given a pull-request event comment, Then it should not display a footer`, () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildPullRequestEventComment(),
        } as unknown as HostElement;
        const render = getCommentFooter(host);

        render(host, target);

        const footer = target.querySelector("[data-test=pullrequest-comment-only-file-name]");

        expect(footer).toBeNull();
    });
});
