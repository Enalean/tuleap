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
    PullRequestCommentComponent,
} from "./PullRequestComment";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import "@tuleap/tlp-relative-date";
import { PullRequestCommentRepliesCollectionPresenter } from "./PullRequestCommentRepliesCollectionPresenter";
import { RelativeDateHelperStub } from "../../tests/stubs/RelativeDateHelperStub";
import type { PullRequestCommentPresenter } from "./PullRequestCommentPresenter";
import { ControlPullRequestCommentStub } from "../../tests/stubs/ControlPullRequestCommentStub";

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

    const render = (comment: PullRequestCommentPresenter): void => {
        const host = {
            comment,
            controller: ControlPullRequestCommentStub(),
            relative_date_helper: RelativeDateHelperStub,
            replies: PullRequestCommentRepliesCollectionPresenter.buildEmpty(),
        } as HostElement;
        const updateFunction = PullRequestCommentComponent.content(host);
        updateFunction(host, target);
    };

    describe("Display", () => {
        it(`Given a not outdated inline comment, then it should have the right classes`, () => {
            render(PullRequestCommentPresenterStub.buildInlineComment());

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const root_classes = Array.from(root.classList);

            expect(root_classes).toContain("pull-request-comment");
            expect(root_classes).toContain("inline-comment");
        });

        it(`Given an outdated inline comment, then it should have the right classes`, () => {
            render(PullRequestCommentPresenterStub.buildInlineCommentOutdated());

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const root_classes = Array.from(root.classList);

            expect(root_classes).toContain("pull-request-comment");
            expect(root_classes).toContain("inline-comment");
        });

        it(`Given a global comment, then it should have the right classes`, () => {
            render(PullRequestCommentPresenterStub.buildGlobalComment());

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const root_classes = Array.from(root.classList);

            expect(root_classes).toContain("pull-request-comment");
            expect(root_classes).toContain("comment");
        });

        it("should execute the post_rendering_callback each time the component height changes", () => {
            vi.useFakeTimers();

            const post_rendering_callback: () => void = vi.fn();
            const host = { post_rendering_callback } as HostElement;

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
    });
});
