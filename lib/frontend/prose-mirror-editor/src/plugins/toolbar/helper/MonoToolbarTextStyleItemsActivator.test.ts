/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { MonoToolbarTextStyleItemsActivator } from "./MonoToolbarTextStyleItemsActivator";
import { RetrieveHeadingStub } from "../text-style/stub/RetrieveHeadingStub";
import { DetectPreformattedTextInSelectionStub } from "../text-style/stub/DetectPreformattedTextInSelectionStub";
import type { ToolbarView } from "./toolbar-bus";
import type { EditorState } from "prosemirror-state";
import { DetectParagraphsInSelectionStub } from "../text-style/stub/DetectParagraphsInSelectionStub";

describe("MonoToolbarTextStyleItemsActivator", () => {
    let toolbar_view: ToolbarView, state: EditorState;

    beforeEach(() => {
        toolbar_view = {
            activateHeading: vi.fn(),
            activatePlainText: vi.fn(),
            activatePreformattedText: vi.fn(),
            activateSubtitle: vi.fn(),
        } as unknown as ToolbarView;

        state = {} as EditorState;
    });

    it("When only a heading is in the current selection, then headings should be activated", () => {
        const current_heading = { level: 3 };
        const activator = MonoToolbarTextStyleItemsActivator(
            RetrieveHeadingStub.withOnlyOneHeading(current_heading),
            DetectPreformattedTextInSelectionStub.withoutOnlyPreformattedText(),
            DetectParagraphsInSelectionStub.withoutOnlyParagraphs(),
        );

        activator.activateTextStyleItems(toolbar_view, state);

        expect(toolbar_view.activateHeading).toHaveBeenCalledWith(current_heading);
        expect(toolbar_view.activatePlainText).toHaveBeenCalledWith(false);
        expect(toolbar_view.activatePreformattedText).toHaveBeenCalledWith(false);
        expect(toolbar_view.activateSubtitle).toHaveBeenCalledWith(false);
    });

    it("When there is only paragraphs in the current selection, then plainText should be activated", () => {
        const activator = MonoToolbarTextStyleItemsActivator(
            RetrieveHeadingStub.withoutHeading(),
            DetectPreformattedTextInSelectionStub.withoutOnlyPreformattedText(),
            DetectParagraphsInSelectionStub.withOnlyParagraphs(),
        );

        activator.activateTextStyleItems(toolbar_view, state);

        expect(toolbar_view.activateHeading).toHaveBeenCalledWith(null);
        expect(toolbar_view.activatePlainText).toHaveBeenCalledWith(true);
        expect(toolbar_view.activatePreformattedText).toHaveBeenCalledWith(false);
        expect(toolbar_view.activateSubtitle).toHaveBeenCalledWith(false);
    });

    it("When there is only preformatted text in the current selection, then preformattedText should be activated", () => {
        const activator = MonoToolbarTextStyleItemsActivator(
            RetrieveHeadingStub.withoutHeading(),
            DetectPreformattedTextInSelectionStub.withOnlyPreformattedText(),
            DetectParagraphsInSelectionStub.withoutOnlyParagraphs(),
        );

        activator.activateTextStyleItems(toolbar_view, state);

        expect(toolbar_view.activateHeading).toHaveBeenCalledWith(null);
        expect(toolbar_view.activatePlainText).toHaveBeenCalledWith(false);
        expect(toolbar_view.activatePreformattedText).toHaveBeenCalledWith(true);
        expect(toolbar_view.activateSubtitle).toHaveBeenCalledWith(false);
    });

    describe("activateSubtitle", () => {
        it("When only a heading 1 is in the current selection, then subtitle should be activated", () => {
            const current_heading = { level: 1 };
            const activator = MonoToolbarTextStyleItemsActivator(
                RetrieveHeadingStub.withOnlyOneHeading(current_heading),
                DetectPreformattedTextInSelectionStub.withoutOnlyPreformattedText(),
                DetectParagraphsInSelectionStub.withoutOnlyParagraphs(),
            );

            activator.activateTextStyleItems(toolbar_view, state);

            expect(toolbar_view.activateSubtitle).toHaveBeenCalledWith(true);
        });

        it("else subtitle should NOT be activated", () => {
            const current_heading = { level: 2 };
            const activator = MonoToolbarTextStyleItemsActivator(
                RetrieveHeadingStub.withOnlyOneHeading(current_heading),
                DetectPreformattedTextInSelectionStub.withoutOnlyPreformattedText(),
                DetectParagraphsInSelectionStub.withoutOnlyParagraphs(),
            );

            activator.activateTextStyleItems(toolbar_view, state);

            expect(toolbar_view.activateSubtitle).toHaveBeenCalledWith(false);
        });
    });

    it("When several headings/preformatted text/paragraphs are detected in the current selection, all styles should be deactivated", () => {
        const activator = MonoToolbarTextStyleItemsActivator(
            RetrieveHeadingStub.withoutHeading(),
            DetectPreformattedTextInSelectionStub.withoutOnlyPreformattedText(),
            DetectParagraphsInSelectionStub.withoutOnlyParagraphs(),
        );

        activator.activateTextStyleItems(toolbar_view, state);

        expect(toolbar_view.activateHeading).toHaveBeenCalledWith(null);
        expect(toolbar_view.activatePlainText).toHaveBeenCalledWith(false);
        expect(toolbar_view.activatePreformattedText).toHaveBeenCalledWith(false);
        expect(toolbar_view.activateSubtitle).toHaveBeenCalledWith(false);
    });
});
