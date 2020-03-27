/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { initProjectWidgetsConfigurationFormSubmission } from "./project-widgets-configuration";

describe("Project widgets configuration form", () => {
    interface LocalForm {
        document: Document;
        form_element: HTMLFormElement;
        switch_element: HTMLInputElement;
    }

    function getLocalForm(): LocalForm {
        const local_document = document.implementation.createHTMLDocument();
        const form_element = local_document.createElement("form");
        form_element.setAttribute("id", "project-widgets-form-1");
        const switch_element = local_document.createElement("input");
        switch_element.setAttribute("class", "tlp-switch-checkbox");
        switch_element.setAttribute("type", "checkbox");
        switch_element.dataset.formId = "project-widgets-form-1";
        form_element.appendChild(switch_element);
        local_document.body.appendChild(form_element);

        return {
            document: local_document,
            form_element: form_element,
            switch_element: switch_element,
        };
    }

    it("Submits the form when clicking on switch", () => {
        const local_form = getLocalForm();

        const submitted = jest.spyOn(local_form.form_element, "submit");

        initProjectWidgetsConfigurationFormSubmission(local_form.document);

        local_form.switch_element.click();

        expect(submitted).toHaveBeenCalledTimes(1);
    });
});
