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

import { describe, it, expect, vi } from "vitest";
import "@tuleap/commonmark-popover/commonmark-popover-stub";
import type { HostElement, NewCommentForm } from "./NewCommentForm";
import {
    PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
    form_height_descriptor,
} from "./NewCommentForm";
import { ControlNewCommentFormStub } from "../../tests/stubs/main";

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));

describe("NewCommentForm", () => {
    it("should execute the post_rendering_callback each time the component height changes", () => {
        vi.useFakeTimers();
        const host = { post_rendering_callback: vi.fn() } as unknown as HostElement;

        form_height_descriptor.observe(host);
        vi.advanceTimersToNextTimer();

        expect(host.post_rendering_callback).toHaveBeenCalledTimes(1);
    });

    it(`should keep the writing zone's comment content up-to-date`, async () => {
        vi.useFakeTimers();
        const new_comment_form = document.createElement(
            PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
        ) as NewCommentForm & HTMLElement;
        new_comment_form.controller = ControlNewCommentFormStub();
        const doc = document.implementation.createHTMLDocument();

        doc.body.append(new_comment_form);
        await vi.runOnlyPendingTimersAsync();
        expect(new_comment_form.writing_zone.comment_content).toBe("");
        expect(new_comment_form.presenter.comment_content).toBe(
            new_comment_form.controller.buildInitialPresenter().comment_content,
        );

        const updated_comment = "unimprovedly intromittent";
        new_comment_form.writing_zone.dispatchEvent(
            new CustomEvent("writing-zone-input", { detail: { content: updated_comment } }),
        );
        await vi.runOnlyPendingTimersAsync();
        expect(new_comment_form.writing_zone.comment_content).toBe(updated_comment);
        expect(new_comment_form.presenter.comment_content).toBe(updated_comment);
    });
});
