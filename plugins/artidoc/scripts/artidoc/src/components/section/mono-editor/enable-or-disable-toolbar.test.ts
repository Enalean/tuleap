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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { TextSelection } from "prosemirror-state";
import { EnableOrDisableToolbarPlugin } from "./enable-or-disable-toolbar";
import {
    initStateWithPlugins,
    initViewWithState,
    SOMEWHERE_IN_THE_DESCRIPTION_POSITION,
    SOMEWHERE_IN_THE_TITLE_POSITION,
} from "./test-mono-editor-helper";
import { getHeadingsButtonState } from "@/toolbar/HeadingsButtonState";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import type { EditorView } from "prosemirror-view";
import type { EditorState } from "prosemirror-state";

describe("enable-or-disable-toolbar", () => {
    let enableToolbar: MockInstance,
        disableToolbar: MockInstance,
        activateHeadingButtonForSection: MockInstance,
        deactivateHeadingButton: MockInstance,
        view: EditorView,
        state: EditorState;

    const setCursorPosition = (position: number): void => {
        view.dispatch(state.tr.setSelection(TextSelection.create(state.doc, position)));
    };

    beforeEach(() => {
        const toolbar_bus = buildToolbarBus();
        const headings_button = getHeadingsButtonState();

        state = initStateWithPlugins([
            EnableOrDisableToolbarPlugin(
                toolbar_bus,
                headings_button,
                ReactiveStoredArtidocSectionStub.fromSection(ArtifactSectionFactory.create()),
            ),
        ]);
        view = initViewWithState(state);

        enableToolbar = vi.spyOn(toolbar_bus, "enableToolbar");
        disableToolbar = vi.spyOn(toolbar_bus, "disableToolbar");
        activateHeadingButtonForSection = vi.spyOn(headings_button, "activateButtonForSection");
        deactivateHeadingButton = vi.spyOn(headings_button, "deactivateButton");
    });

    it("should disable the toolbar and deactivate the headings button when the view is not focused", () => {
        setCursorPosition(SOMEWHERE_IN_THE_TITLE_POSITION);
        expect(disableToolbar).toHaveBeenCalledOnce();
        expect(deactivateHeadingButton).toHaveBeenCalledOnce();
    });

    it("should enable the toolbar and deactivate the headings button, when the current selection is in the description", () => {
        view.focus();
        setCursorPosition(SOMEWHERE_IN_THE_DESCRIPTION_POSITION);
        expect(enableToolbar).toHaveBeenCalledOnce();
        expect(deactivateHeadingButton).toHaveBeenCalledOnce();
    });

    it("should disable the toolbar and active the headings button, when the current selection is in the title", () => {
        view.focus();
        setCursorPosition(SOMEWHERE_IN_THE_TITLE_POSITION);
        expect(disableToolbar).toHaveBeenCalledOnce();
        expect(activateHeadingButtonForSection).toHaveBeenCalledOnce();
    });
});
