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

import {
    ADD_BUTTON_ID,
    ADD_MODAL_ID,
    DELETE_BUTTONS_CLASS,
    DELETE_MODAL_DESCRIPTION,
    DELETE_MODAL_HIDDEN_INPUT_ID,
    DELETE_MODAL_ID,
    setupAddModal,
    setupDeleteButtons
} from "./modals";
import * as tlp from "tlp";
import { GetText } from "src/www/scripts/tuleap/gettext/gettext-init";

jest.mock("tlp");

describe(`OAuth2 server project administration`, () => {
    let doc: Document, createModal: jest.SpyInstance;
    beforeEach(() => {
        doc = createLocalDocument();
        createModal = jest.spyOn(tlp, "modal");
    });

    describe(`setupAddModal()`, () => {
        it(`does not crash when the "Add App" button can't be found`, () => {
            setupAddModal(doc);
            expect(createModal).not.toHaveBeenCalled();
        });

        it(`does not crash when the "Add App" modal element can't be found`, () => {
            createAndAppendElementById(doc, "button", ADD_BUTTON_ID);
            setupAddModal(doc);
            expect(createModal).not.toHaveBeenCalled();
        });

        it(`sets up the Add app modal`, () => {
            const button = createAndAppendElementById(doc, "button", ADD_BUTTON_ID);
            createAndAppendElementById(doc, "div", ADD_MODAL_ID);

            simulateClick(button);
            const modal = {
                show: jest.fn()
            };
            createModal.mockImplementation(() => modal);

            setupAddModal(doc);
            expect(createModal).toHaveBeenCalled();
            expect(modal.show).toHaveBeenCalled();
        });
    });

    describe(`setupDeleteButtons()`, () => {
        let gettext_provider: GetText;
        beforeEach(() => {
            gettext_provider = ({
                gettext: jest.fn()
            } as unknown) as GetText;
        });

        it(`does not crash when there are no "Delete App" buttons`, () => {
            setupDeleteButtons(doc, gettext_provider);

            expect.assertions(0);
        });

        it(`throws when there is no hidden input in the modal`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            simulateClick(delete_button);

            expect(() => setupDeleteButtons(doc, gettext_provider)).toThrow("Missing input hidden");
        });

        it(`throws when there is no "description" tag in the modal`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            createAndAppendElementById(doc, "input", DELETE_MODAL_HIDDEN_INPUT_ID);
            simulateClick(delete_button);

            expect(() => setupDeleteButtons(doc, gettext_provider)).toThrow(
                "Missing description in delete modal"
            );
        });

        it(`throws when the button does not have a data-app-id attribute
            to fill the hidden input`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            createAndAppendElementById(doc, "input", DELETE_MODAL_HIDDEN_INPUT_ID);
            createAndAppendElementById(doc, "p", DELETE_MODAL_DESCRIPTION);
            simulateClick(delete_button);

            expect(() => setupDeleteButtons(doc, gettext_provider)).toThrow(
                "Missing data-app-id attribute on button"
            );
        });

        it(`throws when the button does not have a data-app-name attribute
            to fill the translated description`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            delete_button.dataset.appId = "123";
            createAndAppendElementById(doc, "input", DELETE_MODAL_HIDDEN_INPUT_ID);
            createAndAppendElementById(doc, "p", DELETE_MODAL_DESCRIPTION);
            simulateClick(delete_button);

            expect(() => setupDeleteButtons(doc, gettext_provider)).toThrow(
                "Missing data-app-name attribute on button"
            );
        });

        it(`throws when the "Delete App" modal can't be found`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            delete_button.dataset.appId = "123";
            delete_button.dataset.appName = "My OAuth2 App";
            createAndAppendElementById(doc, "input", DELETE_MODAL_HIDDEN_INPUT_ID);
            createAndAppendElementById(doc, "p", DELETE_MODAL_DESCRIPTION);
            simulateClick(delete_button);

            expect(() => setupDeleteButtons(doc, gettext_provider)).toThrow(
                "Missing modal element"
            );
        });

        it(`when I click on a delete button,
            it will fill the hidden input with the button's data-app-id attribute value,
            and will change the modal's description with the button's data-app-name attribute value
            and will create the TLP modal and show it`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            delete_button.dataset.appId = "123";
            delete_button.dataset.appName = "My OAuth2 App";
            const modal_element = createAndAppendElementById(doc, "div", DELETE_MODAL_ID);
            createAndAppendElementById(doc, "p", DELETE_MODAL_DESCRIPTION);
            const hidden_input = doc.createElement("input");
            hidden_input.setAttribute("id", DELETE_MODAL_HIDDEN_INPUT_ID);
            doc.body.append(hidden_input);
            simulateClick(delete_button);

            const modal = {
                show: jest.fn()
            };
            createModal.mockImplementation(() => modal);

            setupDeleteButtons(doc, gettext_provider);
            expect(hidden_input.value).toEqual("123");
            expect(createModal).toHaveBeenCalledWith(
                modal_element,
                expect.objectContaining({ destroy_on_hide: true })
            );
            expect(modal.show).toHaveBeenCalled();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}

function createAndAppendElementById(doc: Document, tag_name: string, id: string): HTMLElement {
    const element = doc.createElement(tag_name);
    element.setAttribute("id", id);
    doc.body.append(element);
    return element;
}

function createAndAppendDeleteButton(doc: Document): HTMLElement {
    const button = doc.createElement("button");
    button.classList.add(DELETE_BUTTONS_CLASS);
    doc.body.append(button);
    return button;
}

function simulateClick(button: HTMLElement): void {
    jest.spyOn(button, "addEventListener").mockImplementation(
        (event: string, handler: EventListenerOrEventListenerObject) => {
            if (handler instanceof Function) {
                handler(new Event("click"));
            }
        }
    );
}
