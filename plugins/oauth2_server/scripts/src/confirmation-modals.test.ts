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

import * as tlp from "tlp";
import { openModalAndReplacePlaceholders, openModalOnClick } from "./confirmation-modals";

jest.mock("tlp");

describe(`Confirmation modals`, () => {
    let doc: Document, createModal: jest.SpyInstance;
    beforeEach(() => {
        doc = createLocalDocument();
        createModal = jest.spyOn(tlp, "modal");
    });

    describe(`openModalOnClick()`, () => {
        it(`does not crash when the modal element can't be found`, () => {
            openModalOnClick(doc, "unknown_modal_id", "unknown_button_id");
            expect(createModal).not.toHaveBeenCalled();
        });

        it(`does not crash when the button can't be found`, () => {
            createAndAppendElementById(doc, "div", "modal_id");
            openModalOnClick(doc, "modal_id", "unknown_button_id");
            expect(createModal).not.toHaveBeenCalled();
        });

        it(`creates a modal and shows it when the button is clicked`, () => {
            createAndAppendElementById(doc, "div", "modal_id");
            const button = createAndAppendElementById(doc, "button", "button_id");

            simulateClick(button);
            const modal = {
                show: jest.fn()
            };
            createModal.mockImplementation(() => modal);

            openModalOnClick(doc, "modal_id", "button_id");
            expect(createModal).toHaveBeenCalled();
            expect(modal.show).toHaveBeenCalled();
        });
    });

    describe(`openModalAndReplacePlaceholders()`, () => {
        const replaceModalWithOptions = (): void =>
            openModalAndReplacePlaceholders({
                document: doc,
                buttons_selector: ".button-class",
                modal_element_id: "modal_id",
                paragraph_replacement: {
                    paragraph_id: "paragraph_id",
                    paragraphReplaceCallback: (): string => "paragraph_text"
                },
                hidden_input_replacement: {
                    input_id: "hidden_input_id",
                    hiddenInputReplaceCallback: (): string => "hidden_input_value"
                }
            });

        it(`does not crash when there are no buttons`, () => {
            replaceModalWithOptions();

            expect.assertions(0);
        });

        it(`throws when there is no paragraph in the modal`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            simulateClick(delete_button);

            expect(() => replaceModalWithOptions()).toThrow("Missing paragraph in modal");
        });

        it(`throws when there is no hidden input in the modal`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            createAndAppendElementById(doc, "input", "paragraph_id");
            simulateClick(delete_button);

            expect(() => replaceModalWithOptions()).toThrow("Missing input hidden");
        });

        it(`throws when the modal element can't be found`, () => {
            const delete_button = createAndAppendDeleteButton(doc);
            createAndAppendElementById(doc, "input", "paragraph_id");
            createAndAppendElementById(doc, "input", "hidden_input_id");
            simulateClick(delete_button);

            expect(() => replaceModalWithOptions()).toThrow("Missing modal element");
        });

        describe(`when I click on a button`, () => {
            let modal_element: HTMLElement;
            let paragraph: HTMLElement;
            let hidden_input: HTMLInputElement;
            beforeEach(() => {
                const delete_button = createAndAppendDeleteButton(doc);
                modal_element = createAndAppendElementById(doc, "div", "modal_id");
                paragraph = createAndAppendElementById(doc, "p", "paragraph_id");
                hidden_input = doc.createElement("input");
                hidden_input.setAttribute("id", "hidden_input_id");
                hidden_input.value = "value should be replaced";
                doc.body.append(hidden_input);
                simulateClick(delete_button);

                createModal.mockImplementation(() => ({ show: jest.fn() }));
            });

            it(`will replace the paragraph's text using the options' callback`, () => {
                replaceModalWithOptions();

                expect(paragraph.textContent).toEqual("paragraph_text");
            });

            it(`will replace the hidden input's value using the options' callback`, () => {
                replaceModalWithOptions();

                expect(hidden_input.value).toEqual("hidden_input_value");
            });

            it(`will create the TLP modal and show it`, () => {
                const modal = { show: jest.fn() };
                createModal.mockImplementation(() => modal);

                replaceModalWithOptions();

                expect(createModal).toHaveBeenCalledWith(
                    modal_element,
                    expect.objectContaining({ destroy_on_hide: true })
                );
                expect(modal.show).toHaveBeenCalled();
            });
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
    button.classList.add("button-class");
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
