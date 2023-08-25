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

import { describe, it, expect, beforeEach } from "vitest";
import { WritingZonePresenter } from "./WritingZonePresenter";

describe("WritingZonePresenter", () => {
    let initial_presenter: WritingZonePresenter;

    beforeEach(() => {
        initial_presenter = WritingZonePresenter.buildInitial(true);
    });

    it("buildInitial() should return an initial presenter", () => {
        expect(initial_presenter).toStrictEqual({
            initial_content: "",
            is_focused: false,
            is_in_writing_mode: true,
            is_in_preview_mode: false,
            is_comments_markdown_mode_enabled: true,
        });
    });

    it("buildFocused() should return a presenter with is_focused being true", () => {
        expect(WritingZonePresenter.buildFocused(initial_presenter)).toStrictEqual({
            initial_content: "",
            is_focused: true,
            is_in_writing_mode: true,
            is_in_preview_mode: false,
            is_comments_markdown_mode_enabled: true,
        });
    });

    it("buildBlurred() should return a presenter with is_focused being false", () => {
        expect(WritingZonePresenter.buildBlurred(initial_presenter)).toStrictEqual({
            initial_content: "",
            is_focused: false,
            is_in_writing_mode: true,
            is_in_preview_mode: false,
            is_comments_markdown_mode_enabled: true,
        });
    });

    it("buildWritingMode() should return a presenter with is_focused and is_in_writing_mode being true", () => {
        expect(WritingZonePresenter.buildWritingMode(initial_presenter)).toStrictEqual({
            initial_content: "",
            is_focused: true,
            is_in_writing_mode: true,
            is_in_preview_mode: false,
            is_comments_markdown_mode_enabled: true,
        });
    });

    it("buildPreviewMode() should return a presenter with is_focused and is_in_preview_mode being true", () => {
        expect(WritingZonePresenter.buildPreviewMode(initial_presenter)).toStrictEqual({
            initial_content: "",
            is_focused: true,
            is_in_writing_mode: false,
            is_in_preview_mode: true,
            is_comments_markdown_mode_enabled: true,
        });
    });

    it("buildWithContent() should return a presenter with initial_content being the provided string", () => {
        expect(
            WritingZonePresenter.buildWithContent(initial_presenter, "This is new content")
        ).toStrictEqual({
            initial_content: "This is new content",
            is_focused: false,
            is_in_writing_mode: true,
            is_in_preview_mode: false,
            is_comments_markdown_mode_enabled: true,
        });
    });
});
