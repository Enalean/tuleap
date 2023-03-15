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

import { describe, it, beforeEach, expect, vi } from "vitest";
import type { SpyInstance } from "vitest";
import type { HostElement } from "./PullRequestDescriptionComment";
import {
    PullRequestCommentDescriptionComponent,
    after_render_once_descriptor,
} from "./PullRequestDescriptionComment";
import { selectOrThrow } from "@tuleap/dom";
import * as tooltip from "@tuleap/tooltip";
import { RelativeDateHelperStub } from "../../tests/stubs/RelativeDateHelperStub";
import type { ControlPullRequestDescriptionComment } from "./PullRequestDescriptionCommentController";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import { DescriptionAuthorStub } from "../../tests/stubs/DescriptionAuthorStub";

vi.mock("@tuleap/tooltip", () => ({
    loadTooltips: (): void => {
        // do nothing
    },
}));

describe("PullRequestDescriptionComment", () => {
    let target: ShadowRoot, loadTooltips: SpyInstance;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        loadTooltips = vi.spyOn(tooltip, "loadTooltips").mockImplementation(() => {
            // do nothing
        });
    });

    describe("read-mode and write-mode", () => {
        let host: HostElement;

        beforeEach(() => {
            host = {
                description: {
                    author: DescriptionAuthorStub.withDefault(),
                    content: `This commit fixes <a class="cross-reference">bug #123</a>`,
                    raw_content: `This commit fixes bug #123`,
                    post_date: "2023-03-13T15:00:00Z",
                    can_user_update_description: true,
                },
                controller: {
                    showEditionForm: vi.fn(),
                    hideEditionForm: vi.fn(),
                    getRelativeDateHelper: () => RelativeDateHelperStub,
                } as ControlPullRequestDescriptionComment,
            } as HostElement;
        });

        it("When the component is in read-mode, then it should render its content", () => {
            host.edition_form_presenter = null;

            const update = PullRequestCommentDescriptionComponent.content(host);
            update(host, target);

            expect(selectOrThrow(target, "[data-test=comment-author-avatar]")).toBeDefined();
            expect(
                selectOrThrow(target, "[data-test=pull-request-description-read-mode]")
            ).toBeDefined();
        });

        it("When the component is in write-mode, then it should render its content", () => {
            host.edition_form_presenter =
                PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(host.description);

            const update = PullRequestCommentDescriptionComponent.content(host);
            update(host, target);

            expect(selectOrThrow(target, "[data-test=comment-author-avatar]")).toBeDefined();
            expect(
                selectOrThrow(target, "[data-test=pull-request-description-write-mode]")
            ).toBeDefined();
        });
    });

    it("should load tooltips when the component has been rendered", () => {
        const host = {} as HostElement;
        after_render_once_descriptor.observe(host);

        expect(loadTooltips).toHaveBeenCalledTimes(1);
        expect(loadTooltips).toHaveBeenCalledWith(host, false);
    });
});
