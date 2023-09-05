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

import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { toggleIcon, toggleDiffContent, shouldLoadSomeContent } from "./text-follow-up";
import * as tlp_fetch from "@tuleap/tlp-fetch";

jest.mock("@tuleap/tlp-fetch");

describe("Text follow up", () => {
    let get: jest.SpyInstance<Promise<Response>>;
    const changeset_id = "123";
    const field_id = "1911";
    const value = "130";

    beforeEach(() => {
        get = jest.spyOn(tlp_fetch, "get");
    });

    it("Icon is properly defined when diff is toggle", () => {
        const local_document_with_followup = getLocalDocumentWithToggleButton();

        const toggle_diff_buttons =
            local_document_with_followup.getElementsByClassName("toggle-diff");

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
        await toggleDiffContent(
            diff_button_element,
            local_document_with_followup,
            changeset_id,
            field_id,
            "strip-html",
            "show-diff-follow-up",
        );

        expect(get).toHaveBeenCalledWith("/plugins/tracker/changeset/123/diff/strip-html/130/1911");
    });

    it("Displays a specific message when diff is empty", async () => {
        const local_document_with_followup = getLocalDocumentForToggleDiff();

        const diff_buttons = local_document_with_followup.getElementsByClassName("toggle-diff");

        const diff_button_element = diff_buttons[0];

        const only_formatted_message = local_document_with_followup.getElementById(
            `tracker-changeset-only-formatted-diff-info-${changeset_id}-${field_id}`,
        );
        if (!only_formatted_message) {
            throw new Error("Missing only formatted dff message" + changeset_id);
        }

        mockFetchSuccess(get, { return_json: "" });
        await toggleDiffContent(
            diff_button_element,
            local_document_with_followup,
            changeset_id,
            field_id,
            "strip-html",
            "show-diff-follow-up",
        );

        expect(get).toHaveBeenCalledWith("/plugins/tracker/changeset/123/diff/strip-html/130/1911");
        expect(only_formatted_message.classList.contains("hide-only-formatted-diff-message")).toBe(
            false,
        );
    });

    it("Returns true when content have never been loaded", () => {
        const local_document = document.implementation.createHTMLDocument();
        const diff_element = local_document.createElement("div");
        const diff_button = local_document.createElement("button");

        expect(shouldLoadSomeContent(diff_element, diff_button, "strip-html")).toBe(true);
    });

    it("Returns true when content have we switch from html to text", () => {
        const local_document = document.implementation.createHTMLDocument();
        const diff_element = local_document.createElement("div");
        diff_element.setAttribute("last-load-by", "strip-html");
        const diff_button = local_document.createElement("button");

        expect(shouldLoadSomeContent(diff_element, diff_button, "html")).toBe(true);
    });

    it("Returns false when content we are displaying a text diff and when we toggle the diff button", () => {
        const local_document = document.implementation.createHTMLDocument();
        const diff_element = local_document.createElement("div");
        diff_element.setAttribute("last-load-by", "html");
        const diff_button = local_document.createElement("button");
        const icon_element = local_document.createElement("i");
        icon_element.classList.add("fa-caret-down");
        diff_button.appendChild(icon_element);

        expect(shouldLoadSomeContent(diff_element, diff_button, "strip-html")).toBe(false);
    });

    it("Returns true when content we are NO longer displaying a diff and when we toggle the diff button", () => {
        const local_document = document.implementation.createHTMLDocument();
        const diff_element = local_document.createElement("div");
        diff_element.setAttribute("last-load-by", "html");
        const diff_button = local_document.createElement("button");
        const icon_element = local_document.createElement("i");
        icon_element.classList.add("fa-caret-right");
        diff_button.appendChild(icon_element);

        expect(shouldLoadSomeContent(diff_element, diff_button, "strip-html")).toBe(true);
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
        const icon_element = local_document.createElement("i");
        icon_element.classList.add("fa-caret-right");

        diff_button.appendChild(icon_element);

        diff_button.setAttribute("data-changeset-id", changeset_id);

        const markup_diff_button = local_document.createElement("div");
        markup_diff_button.classList.add("markup-diff");
        markup_diff_button.setAttribute("data-changeset-id", changeset_id);

        markup_diff_button.setAttribute("data-field-id", field_id);
        markup_diff_button.setAttribute(
            "id",
            `tracker-changeset-markup-diff-button-${changeset_id}-${field_id}`,
        );

        const diff_element = local_document.createElement("div");
        diff_element.classList.add("diff");
        diff_element.setAttribute(
            "id",
            `tracker-changeset-diff-comment-${changeset_id}-${field_id}`,
        );

        diff_element.setAttribute("data-artifact-id", value);
        diff_element.setAttribute("data-field-id", field_id);

        const error_element = local_document.createElement("div");
        error_element.setAttribute(
            "id",
            `tracker-changeset-diff-error-${changeset_id}-${field_id}`,
        );

        const only_formatting_element = local_document.createElement("div");
        only_formatting_element.setAttribute(
            "id",
            `tracker-changeset-only-formatted-diff-info-${changeset_id}-${field_id}`,
        );
        markup_diff_button.setAttribute("class", "hide-only-formatted-diff-message");

        local_document.body.append(
            diff_button,
            markup_diff_button,
            diff_element,
            error_element,
            only_formatting_element,
        );

        return local_document;
    }
});
