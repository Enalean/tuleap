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

describe("enable-or-disable-toolbar", () => {
    const toolbar_bus = buildToolbarBus();

    const state = initStateWithPlugins([EnableOrDisableToolbarPlugin(toolbar_bus)]);
    const view = initViewWithState(state);

    const setCursorPosition = (position: number): void => {
        view.dispatch(state.tr.setSelection(TextSelection.create(state.doc, position)));
    };

    let enableToolbar: MockInstance;
    let disableToolbar: MockInstance;

    beforeEach(() => {
        enableToolbar = vi.spyOn(toolbar_bus, "enableToolbar");
        disableToolbar = vi.spyOn(toolbar_bus, "disableToolbar");
    });

    it("should enable the toolbar, when the current selection is in the description", () => {
        setCursorPosition(SOMEWHERE_IN_THE_DESCRIPTION_POSITION);
        expect(enableToolbar).toHaveBeenCalledOnce();
    });

    it("should disable the toolbar, when the current selection is in the title", () => {
        setCursorPosition(SOMEWHERE_IN_THE_TITLE_POSITION);
        expect(disableToolbar).toHaveBeenCalledOnce();
    });
});
