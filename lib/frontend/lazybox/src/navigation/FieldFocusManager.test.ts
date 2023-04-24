/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import { FieldFocusManager } from "./FieldFocusManager";
import type { SelectionElement } from "../selection/SelectionElement";

const noop = (): void => {
    // Do nothing
};

describe("FieldFocusManager", () => {
    let selection_element: SelectionElement, source_select_box: HTMLSelectElement;

    beforeEach(() => {
        selection_element = { setFocus: noop } as SelectionElement;
        source_select_box = document.createElement("select");
        source_select_box.setAttribute("tabindex", "-1");
    });

    describe("init", () => {
        it(`When the source <select> has the focus
            Then it sets the focus on the selection element`, () => {
            new FieldFocusManager(source_select_box, selection_element).init();
            const setFocus = vi.spyOn(selection_element, "setFocus");

            source_select_box.dispatchEvent(new Event("focus"));

            expect(setFocus).toHaveBeenCalled();
        });
    });

    describe("destroy", () => {
        it("should remove the focus event listener on the source <select>", () => {
            const focus_manager = new FieldFocusManager(source_select_box, selection_element);
            focus_manager.init();
            const setFocus = vi.spyOn(selection_element, "setFocus");

            source_select_box.dispatchEvent(new Event("focus"));
            focus_manager.destroy();
            source_select_box.dispatchEvent(new Event("focus"));

            expect(setFocus).toHaveBeenCalledTimes(1);
        });
    });
});
