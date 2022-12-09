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

import { selectOrThrow } from "@tuleap/dom";
import * as tooltip from "@tuleap/tooltip";
import type { HostElement } from "./PullRequestComment";
import {
    after_render_once_descriptor,
    element_height_descriptor,
    PullRequestComment,
} from "./PullRequestComment";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import { setCatalog } from "../gettext-catalog";
import "@tuleap/tlp-relative-date";
import { RelativeDateHelperStub } from "../../../tests/stubs/RelativeDateHelperStub";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";

describe("PullRequestComment", () => {
    let target: ShadowRoot, loadTooltips: jest.SpyInstance;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid, getPlural: (nb, msgid) => msgid });

        loadTooltips = jest.spyOn(tooltip, "loadTooltips").mockImplementation();
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    describe("Display", () => {
        it(`Given a not outdated inline comment, then it should have the right classes`, () => {
            const host = {
                comment: PullRequestCommentPresenterStub.buildInlineComment(),
                relativeDateHelper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
            } as unknown as HostElement;
            const update = PullRequestComment.content(host);
            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");

            expect(root.classList).toContain("pull-request-comment");
            expect(root.classList).toContain("inline-comment");
        });

        it(`Given an outdated inline comment, then it should have the right classes`, () => {
            const host = {
                comment: PullRequestCommentPresenterStub.buildInlineCommentOutdated(),
                relativeDateHelper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
            } as unknown as HostElement;
            const update = PullRequestComment.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");

            expect(root.classList).toContain("pull-request-comment");
            expect(root.classList).toContain("inline-comment");
        });

        it(`Given a global comment, then it should have the right classes`, () => {
            const host = {
                comment: PullRequestCommentPresenterStub.buildGlobalComment(),
                relativeDateHelper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
            } as unknown as HostElement;
            const update = PullRequestComment.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");

            expect(root.classList).toContain("pull-request-comment");
            expect(root.classList).toContain("comment");
        });

        it(`Given a pull-request event comment, then it should have the right classes`, () => {
            const host = {
                comment: PullRequestCommentPresenterStub.buildPullRequestEventComment(),
                relativeDateHelper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
            } as unknown as HostElement;
            const update = PullRequestComment.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");

            expect(root.classList).toContain("pull-request-comment");
            expect(root.classList).toContain("timeline-event");
        });

        it("should execute the post_rendering_callback each time the component height changes", () => {
            const post_rendering_callback = jest.fn();
            const host = { post_rendering_callback } as unknown as HostElement;

            element_height_descriptor.observe(host);

            expect(post_rendering_callback).toHaveBeenCalledTimes(1);
        });

        it("should load tooltips when the component has been rendered", () => {
            after_render_once_descriptor.observe();

            expect(loadTooltips).toHaveBeenCalledTimes(1);
        });
    });
});
