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

import { mockFetchSuccess } from "../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import { toggleIcon, toggleDiffContent } from "./text-follow-up";
import * as tlp_fetch from "../../../../src/themes/tlp/src/js/fetch-wrapper";

jest.mock("tlp-fetch");

describe("Text follow up", () => {
    let get: jest.SpyInstance<Promise<Response>>;

    beforeEach(() => {
        get = jest.spyOn(tlp_fetch, "get");
    });

    it("Icon is properly defined when diff is toggle", () => {
        const local_document_with_followup = getLocalDocumentWithToggleButton();

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

    it("Calls the rest route and load the diff", async () => {
        const local_document_with_followup = getLocalDocumentForToggleDiff();

        const diff_buttons = local_document_with_followup.getElementsByClassName("toggle-diff");

        const diff_button_element = diff_buttons[0];

        mockFetchSuccess(get, { return_json: "<div>My diff </div>" });
        await toggleDiffContent(diff_button_element, local_document_with_followup);

        expect(get).toHaveBeenCalledWith("/plugins/tracker/changeset/123/diff/html/130/1911");
    });

    function getLocalDocumentWithToggleButton(): Document {
        const local_document = document.implementation.createHTMLDocument();
        const button_element = local_document.createElement("button");
        button_element.classList.add("toggle-diff");

        const icon_element = local_document.createElement("i");
        icon_element.classList.add("fa-caret-right");

        button_element.appendChild(icon_element);
        local_document.body.appendChild(button_element);

        return local_document;
    }

    function getLocalDocumentForToggleDiff(): Document {
        const local_document = document.implementation.createHTMLDocument();
        const diff_button = local_document.createElement("div");
        diff_button.classList.add("toggle-diff");

        const diff_element = local_document.createElement("div");
        diff_element.classList.add("diff");
        diff_element.setAttribute("data-changeset-id", "123");
        diff_element.setAttribute("data-artifact-id", "130");
        diff_element.setAttribute("data-field-id", "1911");
        diff_element.setAttribute("data-format", "html");

        const error_element = local_document.createElement("div");
        error_element.setAttribute("id", "tracker-changeset-diff-error-123-1911");

        local_document.body.append(diff_button, diff_element, error_element);

        return local_document;
    }
});
