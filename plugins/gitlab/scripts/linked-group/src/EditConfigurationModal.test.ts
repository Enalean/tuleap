/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import * as tlp_modal from "@tuleap/tlp-modal";
import {
    EDIT_CONFIG_SELECTOR,
    EDIT_CONFIGURATION_MODAL_SELECTOR,
    EditConfigurationModal,
    FORM_ELEMENT_DISABLED_CLASSNAME,
    HAS_PREFIX_CHECKBOX_SELECTOR,
    HIDDEN_ICON_CLASSNAME,
    PREFIX_BOX_SELECTOR,
    PREFIX_ICON_SELECTOR,
    PREFIX_INPUT_SELECTOR,
} from "./EditConfigurationModal";
import { selectOrThrow } from "@tuleap/dom";

const noop = (): void => {
    // Do nothing;
};

describe(`EditConfigurationModal`, () => {
    let edit_button: HTMLButtonElement, edit_modal: HTMLElement, modal_instance: tlp_modal.Modal;

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();

        doc.body.insertAdjacentHTML(
            "afterbegin",
            `
            <button id="edit-config-button"></button>
            <div id="edit-config-modal">
              <form id="edit-config-modal-form">
                <input type="checkbox" id="edit-config-modal-allow-artifact-closure">
                <input type="checkbox" id="edit-config-modal-has-prefix">
                <div class="tlp-form-element" id="edit-config-branch-prefix-box">
                  <i id="edit-config-branch-prefix-icon"></i>
                  <input type="text" id="edit-config-branch-prefix">
                </div>
              </form>
            </div>`
        );

        modal_instance = {
            show: noop,
            hide: noop,
        } as tlp_modal.Modal;
        jest.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);

        EditConfigurationModal(doc).init();
        edit_button = selectOrThrow(doc, EDIT_CONFIG_SELECTOR, HTMLButtonElement);
        edit_modal = selectOrThrow(doc, EDIT_CONFIGURATION_MODAL_SELECTOR);
    });

    describe(`enable/disable prefix`, () => {
        let prefix_box: HTMLElement,
            prefix_input: HTMLInputElement,
            prefix_icon: HTMLElement,
            prefix_checkbox: HTMLInputElement;
        beforeEach(() => {
            prefix_box = selectOrThrow(edit_modal, PREFIX_BOX_SELECTOR);
            prefix_input = selectOrThrow(prefix_box, PREFIX_INPUT_SELECTOR, HTMLInputElement);
            prefix_icon = selectOrThrow(prefix_box, PREFIX_ICON_SELECTOR);
            prefix_checkbox = selectOrThrow(
                edit_modal,
                HAS_PREFIX_CHECKBOX_SELECTOR,
                HTMLInputElement
            );
        });

        it(`when I click on the "edit" button, it will show the modal`, () => {
            const show = jest.spyOn(modal_instance, "show");

            edit_button.click();

            expect(show).toHaveBeenCalled();
            expect(prefix_box.classList.contains(FORM_ELEMENT_DISABLED_CLASSNAME)).toBe(true);
            expect(prefix_input.disabled).toBe(true);
            expect(prefix_input.required).toBe(false);
            expect(prefix_icon.classList.contains(HIDDEN_ICON_CLASSNAME)).toBe(true);
        });

        it(`when I check the prefix checkbox, it will enable the prefix_input and set it required`, () => {
            prefix_checkbox.checked = true;
            prefix_checkbox.dispatchEvent(new InputEvent("input"));

            expect(prefix_box.classList.contains(FORM_ELEMENT_DISABLED_CLASSNAME)).toBe(false);
            expect(prefix_input.disabled).toBe(false);
            expect(prefix_input.required).toBe(true);
            expect(prefix_icon.classList.contains(HIDDEN_ICON_CLASSNAME)).toBe(false);
        });

        it(`when I uncheck the prefix checkbox, it will disable the prefix_input`, () => {
            prefix_checkbox.checked = true;
            prefix_checkbox.dispatchEvent(new InputEvent("input"));
            prefix_checkbox.checked = false;
            prefix_checkbox.dispatchEvent(new InputEvent("input"));

            expect(prefix_box.classList.contains(FORM_ELEMENT_DISABLED_CLASSNAME)).toBe(true);
            expect(prefix_input.disabled).toBe(true);
            expect(prefix_input.required).toBe(false);
            expect(prefix_icon.classList.contains(HIDDEN_ICON_CLASSNAME)).toBe(true);
        });
    });
});
