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

import { describe, expect, it, vi } from "vitest";
import "@tuleap/commonmark-popover/commonmark-popover-stub";
import type { EditionForm, HostElement } from "./EditionForm";
import { TAG } from "./EditionForm";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import { ControlEditionFormStub } from "../../../tests/stubs/ControlEditionFormStub";

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));

describe("EditionForm", () => {
    it(`should keep the writing zone's comment content up-to-date`, async () => {
        vi.useFakeTimers();
        const edition_form = document.createElement(TAG) as EditionForm & HTMLElement;
        const initial_comment = "jawbation aphthongia";
        edition_form.comment = PullRequestCommentPresenterStub.buildInlineCommentWithData({
            raw_content: initial_comment,
        });
        edition_form.project_id = 168;
        edition_form.controller = ControlEditionFormStub();
        const doc = document.implementation.createHTMLDocument();

        doc.body.append(edition_form);
        const internal_edition_form = edition_form as HostElement;
        await vi.runOnlyPendingTimersAsync();
        expect(internal_edition_form.writing_zone.comment_content).toBe(initial_comment);
        expect(internal_edition_form.presenter.edited_content).toBe(initial_comment);

        const updated_comment = "hyperalimentation orchestrator";
        internal_edition_form.writing_zone.dispatchEvent(
            new CustomEvent("writing-zone-input", { detail: { content: updated_comment } }),
        );
        await vi.runOnlyPendingTimersAsync();
        expect(internal_edition_form.writing_zone.comment_content).toBe(updated_comment);
        expect(internal_edition_form.presenter.edited_content).toBe(updated_comment);
    });
});
