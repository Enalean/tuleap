/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import { initIconPicker } from "./icon-picker-initializer";
import * as icon_picker from "@joeattardi/emoji-button";

jest.mock("@joeattardi/emoji-button", () => {
    return {
        EmojiButton: jest.fn().mockImplementation(() => {
            return {
                togglePicker: jest.fn(),
                on: jest.fn(),
            };
        }),
    };
});

describe("icon-picker-initializer", () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("does not initialize the icon picker if the button and the input are not found", () => {
        const icon_picker_instance = new icon_picker.EmojiButton();

        const whatever_button = doc.createElement("button");
        whatever_button.id = "not-icon-picker-button";

        const whatever_input = doc.createElement("input");
        whatever_input.id = "not-icon-picker-input";

        doc.body.append(whatever_button, whatever_input);

        initIconPicker(doc, icon_picker_instance);
        whatever_button.click();
        expect(icon_picker_instance.on).not.toHaveBeenCalled();
    });

    it("does not initialize the icon picker if the picker is not built", () => {
        const icon_picker_instance = null;

        const icon_removal_button = doc.createElement("button");
        icon_removal_button.id = "icon-removal-button";

        const icon_input = doc.createElement("input");
        icon_input.id = "icon-input";
        const input_spy = jest.spyOn(icon_input, "setAttribute");

        doc.body.append(icon_removal_button, icon_input);

        initIconPicker(doc, icon_picker_instance);
        icon_removal_button.click();

        expect(input_spy).not.toHaveBeenCalled();
    });

    it("does initialize the icon picker if the button and the input are found", () => {
        const icon_picker_instance = new icon_picker.EmojiButton();

        const icon_removal_button = doc.createElement("button");
        icon_removal_button.id = "icon-removal-button";

        const icon_input = doc.createElement("input");
        icon_input.id = "icon-input";

        doc.body.append(icon_removal_button, icon_input);

        initIconPicker(doc, icon_picker_instance);
        expect(icon_picker_instance.on).toHaveBeenCalled();
    });

    it("does not show the icon removal button when there is no selected icon", () => {
        const icon_picker_instance = new icon_picker.EmojiButton();

        const icon_removal_button = doc.createElement("button");
        icon_removal_button.id = "icon-removal-button";

        const icon_input = doc.createElement("input");
        icon_input.id = "icon-input";
        icon_input.value = "happy";

        doc.body.append(icon_removal_button, icon_input);

        initIconPicker(doc, icon_picker_instance);
        expect(icon_removal_button.classList.contains("show")).toBe(true);
    });
});
