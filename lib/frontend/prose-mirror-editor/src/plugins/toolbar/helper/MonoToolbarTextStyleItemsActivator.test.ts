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
import { DetectHeadingsInSelectionStub } from "../text-style/stub/DetectHeadingsInSelectionStub";
import type { ToolbarView } from "./toolbar-bus";
import type { EditorState } from "prosemirror-state";

describe("MonoToolbarTextStyleItemsActivator", () => {
    let toolbar_view: ToolbarView, state: EditorState;

    beforeEach(() => {
        toolbar_view = {
            activateHeading: vi.fn(),
            activatePlainText: vi.fn(),
        } as unknown as ToolbarView;

        state = {} as EditorState;
    });

    it("When only a heading is in the current selection, then headings should be activated", () => {
        const current_heading = { level: 1 };
        const activator = MonoToolbarTextStyleItemsActivator(
            RetrieveHeadingStub.withOnlyOneHeading(current_heading),
            DetectHeadingsInSelectionStub.withAtLeastOneHeading(),
        );

        activator.activateTextStyleItems(toolbar_view, state);

        expect(toolbar_view.activateHeading).toHaveBeenCalledWith(current_heading);
        expect(toolbar_view.activatePlainText).toHaveBeenCalledWith(false);
    });

    it("When no heading is in the current selection, then plainText should be activated", () => {
        const activator = MonoToolbarTextStyleItemsActivator(
            RetrieveHeadingStub.withoutHeading(),
            DetectHeadingsInSelectionStub.withoutHeadings(),
        );

        activator.activateTextStyleItems(toolbar_view, state);

        expect(toolbar_view.activateHeading).toHaveBeenCalledWith(null);
        expect(toolbar_view.activatePlainText).toHaveBeenCalledWith(true);
    });

    it("When several headings are detected in the current selection, then no plainText nor headings should be activated", () => {
        const activator = MonoToolbarTextStyleItemsActivator(
            RetrieveHeadingStub.withoutHeading(),
            DetectHeadingsInSelectionStub.withAtLeastOneHeading(),
        );

        activator.activateTextStyleItems(toolbar_view, state);

        expect(toolbar_view.activateHeading).toHaveBeenCalledWith(null);
        expect(toolbar_view.activatePlainText).toHaveBeenCalledWith(false);
    });
});
