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

import type { HostElement } from "./SelectBoxField";
import { highlightSelectBoxField } from "./SelectBoxHighlighter";

describe("SelectBoxHighlighter", () => {
    it("should highlight the select box field for 2 seconds", () => {
        jest.useFakeTimers();

        const doc = document.implementation.createHTMLDocument();
        const field_element = doc.createElement("div");
        const list_picker_element = doc.createElement("span");

        list_picker_element.setAttribute("data-list-picker", "wrapper");
        field_element.append(list_picker_element);

        highlightSelectBoxField({
            content: (): HTMLElement => field_element,
        } as HostElement);

        expect(
            list_picker_element.classList.contains("select-box-allowed-values-updated-highlight")
        ).toBe(true);
        jest.advanceTimersByTime(2000);
        expect(
            list_picker_element.classList.contains("select-box-allowed-values-updated-highlight")
        ).toBe(false);
    });
});
