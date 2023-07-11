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

import { describe, it, beforeEach, vi, expect } from "vitest";
import * as tlp_modal from "@tuleap/tlp-modal";
import * as fetch_result from "@tuleap/fetch-result";
import {
    EDIT_CONFIG_SELECTOR,
    EDIT_CONFIGURATION_MODAL_SELECTOR,
    EDIT_CONFIRM_ICON_SELECTOR,
    EDIT_CONFIRM_SELECTOR,
    EditConfigurationModal,
    HAS_PREFIX_CHECKBOX_SELECTOR,
    PREFIX_BOX_SELECTOR,
    PREFIX_ICON_SELECTOR,
    PREFIX_INPUT_SELECTOR,
    ALLOW_ARTIFACT_CLOSURE_INPUT_SELECTOR,
    ALLOW_ARTIFACT_CLOSURE_DISPLAY_SELECTOR,
    PREFIX_DISPLAY_SELECTOR,
    MODAL_FEEDBACK_SELECTOR,
    FORM_ELEMENTS_SELECTOR,
    INPUTS_SELECTOR,
} from "./EditConfigurationModal";
import { selectOrThrow } from "@tuleap/dom";
import type { GetText } from "@tuleap/gettext";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import {
    FEEDBACK_HIDDEN_CLASSNAME,
    FORM_ELEMENT_DISABLED_CLASSNAME,
    HIDDEN_ICON_CLASSNAME,
} from "./classnames";
import { uri } from "@tuleap/fetch-result";

const noop = (): void => {
    // Do nothing;
};

const GROUP_LINK_ID = 33;
const BRANCH_PREFIX = "dev-";

vi.mock("@tuleap/fetch-result");

describe(`EditConfigurationModal`, () => {
    let edit_button: HTMLButtonElement,
        edit_modal: HTMLElement,
        modal_instance: tlp_modal.Modal,
        prefix_checkbox: HTMLInputElement,
        body: HTMLElement;

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        const gettext = {
            gettext: (msgid: string) => msgid,
        } as GetText;

        doc.body.insertAdjacentHTML(
            "afterbegin",
            `
            <p id="group-information-allow-artifact-closure"></p>
            <p id="group-information-branch-prefix"></p>
            <button id="edit-config-button"></button>
            <div id="edit-config-modal">
              <form id="edit-config-modal-form">
                <div id="edit-config-modal-feedback" class="${FEEDBACK_HIDDEN_CLASSNAME}">
                  <div id="edit-config-modal-alert"></div>
                </div>
                <div data-form-element>
                  <input type="checkbox" id="edit-config-modal-allow-artifact-closure">
                </div>
                <div data-form-element>
                  <input type="checkbox" id="edit-config-modal-has-prefix">
                </div>
                <div class="tlp-form-element" id="edit-config-branch-prefix-box" data-form-element>
                  <i id="edit-config-branch-prefix-icon"></i>
                  <input type="text" id="edit-config-branch-prefix">
                </div>
                <button type="submit" id="edit-config-confirm">
                  <i id="edit-icon" class="${HIDDEN_ICON_CLASSNAME}"></i>
                </button>
              </form>
            </div>`
        );

        modal_instance = {
            show: noop,
            hide: noop,
        } as tlp_modal.Modal;
        vi.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);

        EditConfigurationModal(doc, gettext, GROUP_LINK_ID).init();
        body = doc.body;
        edit_button = selectOrThrow(doc, EDIT_CONFIG_SELECTOR, HTMLButtonElement);
        edit_modal = selectOrThrow(doc, EDIT_CONFIGURATION_MODAL_SELECTOR);
        prefix_checkbox = selectOrThrow(edit_modal, HAS_PREFIX_CHECKBOX_SELECTOR, HTMLInputElement);
    });

    describe(`enable/disable prefix`, () => {
        let prefix_box: HTMLElement, prefix_input: HTMLInputElement, prefix_icon: HTMLElement;
        beforeEach(() => {
            prefix_box = selectOrThrow(edit_modal, PREFIX_BOX_SELECTOR);
            prefix_input = selectOrThrow(prefix_box, PREFIX_INPUT_SELECTOR, HTMLInputElement);
            prefix_icon = selectOrThrow(prefix_box, PREFIX_ICON_SELECTOR);
        });

        it(`when I click on the "edit" button, it will show the modal`, () => {
            const show = vi.spyOn(modal_instance, "show");

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

    describe(`when I click the "confirm" button in the modal`, () => {
        let confirm_button: HTMLButtonElement,
            button_icon: HTMLElement,
            feedback: HTMLElement,
            prefix_input: HTMLInputElement;

        beforeEach(() => {
            confirm_button = selectOrThrow(edit_modal, EDIT_CONFIRM_SELECTOR, HTMLButtonElement);
            button_icon = selectOrThrow(edit_modal, EDIT_CONFIRM_ICON_SELECTOR);
            feedback = selectOrThrow(edit_modal, MODAL_FEEDBACK_SELECTOR);

            const allow_closure_checkbox = selectOrThrow(
                edit_modal,
                ALLOW_ARTIFACT_CLOSURE_INPUT_SELECTOR,
                HTMLInputElement
            );
            prefix_input = selectOrThrow(edit_modal, PREFIX_INPUT_SELECTOR, HTMLInputElement);
            allow_closure_checkbox.checked = true;
            prefix_checkbox.checked = true;
            prefix_input.value = BRANCH_PREFIX;
        });

        function assertLoadingState(is_loading: boolean): void {
            const icon_classes = button_icon.classList;
            expect(icon_classes.contains(HIDDEN_ICON_CLASSNAME)).toBe(!is_loading);
            expect(confirm_button.disabled).toBe(is_loading);
            edit_modal.querySelectorAll(FORM_ELEMENTS_SELECTOR).forEach((form_element) => {
                expect(form_element.classList.contains(FORM_ELEMENT_DISABLED_CLASSNAME)).toBe(
                    is_loading
                );
            });
            edit_modal.querySelectorAll(INPUTS_SELECTOR).forEach((input) => {
                expect(input.disabled).toBe(is_loading);
            });
        }

        it(`will show a spinner, disable the form elements, call the REST route,
            close the modal and update the page information`, async () => {
            const result = okAsync({
                allow_artifact_closure: true,
                create_branch_prefix: BRANCH_PREFIX,
            });
            const patchSpy = vi.spyOn(fetch_result, "patchJSON").mockReturnValue(result);
            const modalHide = vi.spyOn(modal_instance, "hide");

            confirm_button.click();

            expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(true);
            assertLoadingState(true);

            await result;

            assertLoadingState(false);
            expect(patchSpy).toHaveBeenCalledWith(uri`/api/gitlab_groups/${GROUP_LINK_ID}`, {
                allow_artifact_closure: true,
                create_branch_prefix: BRANCH_PREFIX,
            });
            const allow_closure_display = selectOrThrow(
                body,
                ALLOW_ARTIFACT_CLOSURE_DISPLAY_SELECTOR
            );
            const prefix_display = selectOrThrow(body, PREFIX_DISPLAY_SELECTOR);
            expect(allow_closure_display.textContent).toBe("Yes");
            expect(prefix_display.textContent).toBe(BRANCH_PREFIX);
            expect(modalHide).toHaveBeenCalled();
        });

        it(`when prefix checkbox is unchecked, it will clear the value of the prefix input`, async () => {
            const result = okAsync({
                allow_artifact_closure: true,
                create_branch_prefix: "",
            });
            const patchSpy = vi.spyOn(fetch_result, "patchJSON").mockReturnValue(result);
            prefix_checkbox.checked = false;

            confirm_button.click();
            await result;

            expect(patchSpy).toHaveBeenCalledWith(uri`/api/gitlab_groups/${GROUP_LINK_ID}`, {
                allow_artifact_closure: true,
                create_branch_prefix: "",
            });
            expect(prefix_input.value).toBe("");
        });

        it(`and there is a REST error, it will show an error message in the modal feedback`, async () => {
            const error_message = "Forbidden";
            const result = errAsync(Fault.fromMessage(error_message));
            vi.spyOn(fetch_result, "patchJSON").mockReturnValue(result);
            const modalHide = vi.spyOn(modal_instance, "hide");

            confirm_button.click();
            await result;

            expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(false);
            expect(feedback.textContent).toContain(error_message);
            expect(modalHide).not.toHaveBeenCalled();
        });
    });
});
