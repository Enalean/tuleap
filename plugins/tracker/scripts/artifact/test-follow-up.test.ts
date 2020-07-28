/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import { toggleIcon } from "./text-follow-up";

describe("Text follow up", () => {
    it("Icon is properly defined when diff is toggle", () => {
        const local_document_with_followup = getLocalDocumentWithTextFollowUp();

        const toggle_diff_buttons = local_document_with_followup.getElementsByClassName(
            "toggle-diff"
        );

        const toggle_button = toggle_diff_buttons[0];
        toggleIcon(toggle_button);

        let right_icon_list = toggle_button.getElementsByClassName("fa-caret-right");
        let left_icon_list = toggle_button.getElementsByClassName("fa-caret-down");

        expect(left_icon_list[0]).toBeDefined();
        expect(right_icon_list[0]).toBeUndefined();

        toggleIcon(toggle_button);

        right_icon_list = toggle_button.getElementsByClassName("fa-caret-right");
        left_icon_list = toggle_button.getElementsByClassName("fa-caret-down");

        expect(left_icon_list[0]).toBeUndefined();
        expect(right_icon_list[0]).toBeDefined();
    });

    function getLocalDocumentWithTextFollowUp(): Document {
        const local_document = document.implementation.createHTMLDocument();
        const button_element = local_document.createElement("button");
        button_element.classList.add("toggle-diff");

        const icon_element = local_document.createElement("i");
        icon_element.classList.add("fa-caret-right");

        button_element.appendChild(icon_element);
        local_document.body.appendChild(button_element);

        return local_document;
    }
});
