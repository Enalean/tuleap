/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { setupFormSubmission } from "./admin-header-background";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("admin-header-background", () => {
    it("sets a project header background", async () => {
        const { mount_point, form } = createDocumentExpectedFormStructure("beach-daytime");
        const location = { ...window.location, reload: jest.fn() };

        const tlpPut = jest.spyOn(tlp_fetch, "put");

        mockFetchSuccess(tlpPut);

        setupFormSubmission(mount_point, location);

        await form.submit();

        expect(location.hash).toBe("#header-background-change-success");
        expect(location.reload).toHaveBeenCalled();
    });

    it("removes a project header background", async () => {
        const { mount_point, form } = createDocumentExpectedFormStructure("0");
        const location = { ...window.location, reload: jest.fn() };

        const tlpDelete = jest.spyOn(tlp_fetch, "del");

        mockFetchSuccess(tlpDelete);

        setupFormSubmission(mount_point, location);

        await form.submit();

        expect(location.hash).toBe("#header-background-change-success");
        expect(location.reload).toHaveBeenCalled();
    });

    it("shows a success message when it seems a background has been changed", () => {
        const { mount_point, success_element } =
            createDocumentExpectedFormStructure("beach-daytime");

        const class_hide_feedback = "project-admin-background-feedback-hidden";
        success_element.classList.add(class_hide_feedback);

        const location = window.location;
        location.hash = "#header-background-change-success";

        setupFormSubmission(mount_point, location);

        expect(success_element.classList.contains(class_hide_feedback)).toBe(false);
    });

    it("throws an error if the form cannot be found", () => {
        expect(() =>
            setupFormSubmission(document.implementation.createHTMLDocument(), window.location),
        ).toThrowError();
    });

    it("throws an error if the error message cannot be found", () => {
        const mount_point = document.implementation.createHTMLDocument();
        const form = mount_point.createElement("form");
        form.setAttribute("id", "form-header-background");
        mount_point.body.appendChild(form);

        expect(() => setupFormSubmission(mount_point, window.location)).toThrowError();
    });

    it("throws an error the success message cannot be found", () => {
        const mount_point = document.implementation.createHTMLDocument();
        const form = mount_point.createElement("form");
        form.setAttribute("id", "form-header-background");
        const error_element = mount_point.createElement("div");
        error_element.setAttribute("id", "project-admin-background-error");
        form.appendChild(error_element);
        mount_point.body.appendChild(form);

        expect(() => setupFormSubmission(mount_point, window.location)).toThrowError();
    });

    it("throws an error if the submit button cannot be found", () => {
        const mount_point = document.implementation.createHTMLDocument();
        const form = mount_point.createElement("form");
        form.setAttribute("id", "form-header-background");
        const error_element = mount_point.createElement("div");
        error_element.setAttribute("id", "project-admin-background-error");
        form.appendChild(error_element);
        const success_element = mount_point.createElement("div");
        success_element.setAttribute("id", "project-admin-background-success");
        form.appendChild(success_element);
        mount_point.body.appendChild(form);

        expect(() => setupFormSubmission(mount_point, window.location)).toThrowError();
    });

    it("throws an error if the submit button icon cannot be found", () => {
        const mount_point = document.implementation.createHTMLDocument();
        const form = mount_point.createElement("form");
        form.setAttribute("id", "form-header-background");
        const error_element = mount_point.createElement("div");
        error_element.setAttribute("id", "project-admin-background-error");
        form.appendChild(error_element);
        const success_element = mount_point.createElement("div");
        success_element.setAttribute("id", "project-admin-background-success");
        form.appendChild(success_element);
        const submit_button = mount_point.createElement("button");
        submit_button.setAttribute("id", "project-admin-background-submit-button");
        form.appendChild(submit_button);
        mount_point.body.appendChild(form);

        expect(() => setupFormSubmission(mount_point, window.location)).toThrowError();
    });

    function createDocumentExpectedFormStructure(new_background_identifier: string): {
        mount_point: Document;
        form: HTMLFormElement;
        success_element: HTMLElement;
    } {
        const mount_point = document.implementation.createHTMLDocument();
        const form = mount_point.createElement("form");
        form.setAttribute("id", "form-header-background");
        const project_id_input = mount_point.createElement("input");
        project_id_input.name = "project-id";
        project_id_input.value = "102";
        form.appendChild(project_id_input);
        const new_background_input = mount_point.createElement("input");
        new_background_input.name = "new-background";
        new_background_input.value = new_background_identifier;
        form.appendChild(new_background_input);
        const error_element = mount_point.createElement("div");
        error_element.setAttribute("id", "project-admin-background-error");
        form.appendChild(error_element);
        const success_element = mount_point.createElement("div");
        success_element.setAttribute("id", "project-admin-background-success");
        form.appendChild(success_element);
        const submit_button = mount_point.createElement("button");
        submit_button.setAttribute("id", "project-admin-background-submit-button");
        const submit_button_icon = mount_point.createElement("i");
        submit_button_icon.setAttribute("id", "project-admin-background-submit-button-icon");
        submit_button.appendChild(submit_button_icon);
        form.appendChild(submit_button);
        mount_point.body.appendChild(form);

        return { mount_point, form, success_element };
    }
});
