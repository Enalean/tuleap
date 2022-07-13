/*
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

import { describe, it, expect, beforeEach } from "vitest";
import { isListPickerInAModal } from "./list-picker-in-modals-helper";

describe("list-picker-in-modals-helper", () => {
    let wrapper_element: HTMLElement;

    function getModalBodyWithClass(modal_class: string): HTMLElement {
        const modal_body = document.createElement("div");
        modal_body.setAttribute("class", modal_class);
        return modal_body;
    }

    beforeEach(() => {
        wrapper_element = document.createElement("span");
    });

    describe("isListPickerInAModal", () => {
        it("should return true when the wrapper element is in a tlp modal", () => {
            const modal = getModalBodyWithClass("tlp-modal-body");
            modal.appendChild(wrapper_element);
            expect(isListPickerInAModal(wrapper_element)).toBe(true);
        });

        it("should return true when the wrapper element is in a bootstrap modal", () => {
            const modal = getModalBodyWithClass("modal-body");
            modal.appendChild(wrapper_element);
            expect(isListPickerInAModal(wrapper_element)).toBe(true);
        });

        it("should return false otherwise", () => {
            const form_element = document.createElement("div");
            form_element.appendChild(wrapper_element);
            expect(isListPickerInAModal(wrapper_element)).toBe(false);
        });
    });
});
