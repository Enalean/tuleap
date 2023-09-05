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
import type { SpyInstance } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import * as tooltip from "@tuleap/tooltip";
import type { HostElement } from "./PullRequestComment";
import {
    after_render_once_descriptor,
    element_height_descriptor,
    post_reply_save_callback_descriptor,
    PullRequestCommentComponent,
} from "./PullRequestComment";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import "@tuleap/tlp-relative-date";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";
import { RelativeDateHelperStub } from "../../tests/stubs/RelativeDateHelperStub";

vi.mock("@tuleap/tooltip", () => ({
    loadTooltips: (): void => {
        // do nothing
    },
}));

describe("PullRequestComment", () => {
    let target: ShadowRoot, loadTooltips: SpyInstance;

    beforeEach(() => {
        loadTooltips = vi.spyOn(tooltip, "loadTooltips").mockImplementation(() => {
            // do nothing
        });

        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    describe("Display", () => {
        it(`Given a not outdated inline comment, then it should have the right classes`, () => {
            const host = {
                comment: PullRequestCommentPresenterStub.buildInlineComment(),
                relative_date_helper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
            } as unknown as HostElement;
            const update = PullRequestCommentComponent.content(host);
            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const root_classes = Array.from(root.classList);

            expect(root_classes).toContain("pull-request-comment");
            expect(root_classes).toContain("inline-comment");
        });

        it(`Given an outdated inline comment, then it should have the right classes`, () => {
            const host = {
                comment: PullRequestCommentPresenterStub.buildInlineCommentOutdated(),
                relative_date_helper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
            } as unknown as HostElement;
            const update = PullRequestCommentComponent.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const root_classes = Array.from(root.classList);

            expect(root_classes).toContain("pull-request-comment");
            expect(root_classes).toContain("inline-comment");
        });

        it(`Given a global comment, then it should have the right classes`, () => {
            const host = {
                comment: PullRequestCommentPresenterStub.buildGlobalComment(),
                relative_date_helper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
            } as unknown as HostElement;
            const update = PullRequestCommentComponent.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const root_classes = Array.from(root.classList);

            expect(root_classes).toContain("pull-request-comment");
            expect(root_classes).toContain("comment");
        });

        it(`Given a pull-request event comment, then it should have the right classes`, () => {
            const host = {
                comment: PullRequestCommentPresenterStub.buildPullRequestEventComment(),
                relative_date_helper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
            } as unknown as HostElement;
            const update = PullRequestCommentComponent.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const root_classes = Array.from(root.classList);

            expect(root_classes).toContain("pull-request-comment");
            expect(root_classes).toContain("timeline-event");
        });

        it("should execute the post_rendering_callback each time the component height changes", () => {
            vi.useFakeTimers();

            const post_rendering_callback = vi.fn();
            const host = { post_rendering_callback } as unknown as HostElement;

            element_height_descriptor.observe(host);

            vi.advanceTimersToNextTimer();
            expect(post_rendering_callback).toHaveBeenCalledTimes(1);
        });

        it("should load tooltips when the component has been rendered", () => {
            const host = {} as HostElement;
            after_render_once_descriptor.observe(host);

            expect(loadTooltips).toHaveBeenCalledTimes(1);
            expect(loadTooltips).toHaveBeenCalledWith(host, false);
        });

        it("should load tooltips inside the latest reply when is has just been saved and rendered", () => {
            const last_reply_text = "Last reply";
            const host = {
                comment: PullRequestCommentPresenterStub.buildInlineComment(),
                relative_date_helper: RelativeDateHelperStub,
                replies: PullRequestCommentRepliesCollectionPresenter.fromReplies([
                    PullRequestCommentPresenterStub.buildInlineCommentWithData({
                        post_processed_content: "First reply",
                    }),
                    PullRequestCommentPresenterStub.buildInlineCommentWithData({
                        post_processed_content: last_reply_text,
                    }),
                ]),
                content: () => target,
            } as unknown as HostElement;

            const update = PullRequestCommentComponent.content(host);
            update(host, target);
            post_reply_save_callback_descriptor.get(host)();

            expect(loadTooltips).toHaveBeenCalledTimes(1);

            const tooltip_target = loadTooltips.mock.calls[0][0];
            expect(
                selectOrThrow(
                    tooltip_target,
                    "[data-test=pull-request-comment-text]",
                ).textContent?.trim(),
            ).toStrictEqual(last_reply_text);
        });
    });
});
