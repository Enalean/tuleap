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

import { describe, it, expect, vi } from "vitest";
import type { InternalSelectorsDropdown } from "./SelectorsDropdown";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import { SelectorsDropdownController } from "./SelectorsDropdownController";

vi.mock("@tuleap/tlp-dropdown");

describe("SelectorsDropdownController", () => {
    it(`initDropdown() should init a tlp-dropdown using the custom-element's button and menu`, () => {
        const doc = document.implementation.createHTMLDocument();
        const host = {
            dropdown_button_element: doc.createElement("button"),
            dropdown_content_element: doc.createElement("div"),
        } as unknown as InternalSelectorsDropdown;

        const createDropdown = vi.spyOn(tlp_dropdown, "createDropdown");

        SelectorsDropdownController().initDropdown(host);

        expect(createDropdown).toHaveBeenCalledWith(host.dropdown_button_element, {
            dropdown_menu: host.dropdown_content_element,
        });
    });
});
