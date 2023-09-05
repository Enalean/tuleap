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

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { Modal } from "./modal";
import {
    BACKDROP_ID,
    BACKDROP_SHOWN_CLASS_NAME,
    createModal,
    EVENT_TLP_MODAL_HIDDEN,
    EVENT_TLP_MODAL_SHOWN,
    EVENT_TLP_MODAL_WILL_HIDE,
    MODAL_SHOWN_CLASS_NAME,
} from "./modal";
import { selectOrThrow } from "@tuleap/dom";

describe(`Modal`, () => {
    let modal_element: HTMLElement, doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        modal_element = doc.createElement("div");
        doc.body.append(modal_element);
    });

    describe(`show()`, () => {
        let modal: Modal;
        beforeEach(() => {
            modal = createModal(doc, modal_element);
        });
        afterEach(() => {
            modal.destroy();
        });

        it(`will add the "display" and "shown" CSS classes to the modal element`, () => {
            modal.show();
            expectTheModalToBeShown(modal_element);
        });

        it(`will dispatch the "shown" event on modal`, () => {
            let event_dispatched = false;
            modal.addEventListener(EVENT_TLP_MODAL_SHOWN, () => {
                event_dispatched = true;
            });
            modal.show();
            expect(event_dispatched).toBe(true);
        });

        it(`will dispatch the "shown" event on Document`, () => {
            let event_dispatched = false;
            doc.addEventListener(EVENT_TLP_MODAL_SHOWN, () => {
                event_dispatched = true;
            });
            modal.show();
            expect(event_dispatched).toBe(true);
        });

        it(`will create and show a backdrop element`, () => {
            modal.show();
            const backdrop = selectOrThrow(doc, `#${BACKDROP_ID}`);
            expect(backdrop.classList.contains(BACKDROP_SHOWN_CLASS_NAME)).toBe(true);
        });
    });

    describe(`hide()`, () => {
        let modal: Modal;

        describe(`for a regular modal`, () => {
            beforeEach(() => {
                modal = createModal(doc, modal_element);
            });
            afterEach(() => {
                modal.destroy();
            });

            it(`will remove the "shown" CSS class from the modal element`, () => {
                modal.show();
                modal.hide();
                expectTheModalToBeHidden(modal_element);
            });

            it(`will remove the "backdrop shown" CSS class from the backdrop element`, () => {
                modal.show();
                const backdrop = selectOrThrow(doc, `#${BACKDROP_ID}`);
                modal.hide();
                expect(backdrop.classList.contains(BACKDROP_SHOWN_CLASS_NAME)).toBe(false);
            });

            it(`will remove the backdrop element`, () => {
                modal.show();
                modal.hide();

                const backdrop = doc.querySelector("#" + BACKDROP_ID);
                expect(backdrop).toBeNull();
            });

            it(`will dispatch the "hidden" event on modal`, () => {
                modal.show();
                let event_dispatched = false;
                modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => {
                    event_dispatched = true;
                });

                modal.hide();
                expect(event_dispatched).toBe(true);
            });

            it(`will dispatch the "hidden" event on Document`, () => {
                modal.show();
                let event_dispatched = false;
                doc.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => {
                    event_dispatched = true;
                });

                modal.hide();
                expect(event_dispatched).toBe(true);
            });

            it(`when I hide a modal that was never "shown" first, it will not crash`, () => {
                expect(() => {
                    modal.hide();
                }).not.toThrow();
            });
        });

        it(`given the modal had the "destroy_on_hide" option, it will destroy the modal`, () => {
            const first_closing_element = doc.createElement("span");
            first_closing_element.dataset.dismiss = "modal";
            const removeFirstClickListener = vi.spyOn(first_closing_element, "removeEventListener");
            const second_closing_element = doc.createElement("span");
            second_closing_element.dataset.dismiss = "modal";
            const removeSecondClickListener = vi.spyOn(
                second_closing_element,
                "removeEventListener",
            );
            modal_element.append(first_closing_element, second_closing_element);
            modal = createModal(doc, modal_element, { destroy_on_hide: true });
            modal.show();
            modal.hide();

            expect(removeFirstClickListener).toHaveBeenCalled();
            expect(removeSecondClickListener).toHaveBeenCalled();
        });
    });

    describe(`toggle()`, () => {
        let modal: Modal;
        beforeEach(() => {
            modal = createModal(doc, modal_element);
        });
        afterEach(() => {
            modal.destroy();
        });

        it(`when the modal is hidden, it will show it`, () => {
            modal.toggle();

            expectTheModalToBeShown(modal_element);

            modal.hide();
        });

        it(`when the modal is shown, it will hide it`, () => {
            modal.show();
            modal.toggle();

            expectTheModalToBeHidden(modal_element);
        });
    });

    describe(`bringFocusInsideModal()`, () => {
        let modal: Modal;
        beforeEach(() => {
            modal = createModal(doc, modal_element);
        });
        afterEach(() => {
            modal.destroy();
        });

        it(`focuses the element with a data-modal-focus attribute in priority when modal opens`, () => {
            const form_element = doc.createElement("input");
            const data_modal_focus_element = doc.createElement("div");
            data_modal_focus_element.setAttribute("data-modal-focus", "");
            modal_element.append(form_element, data_modal_focus_element);
            const focus = vi.spyOn(data_modal_focus_element, "focus");

            modal.show();

            expect(focus).toHaveBeenCalled();
        });

        it(`focuses the first form element when modal opens`, () => {
            const form_element = doc.createElement("input");
            modal_element.appendChild(form_element);
            const focus = vi.spyOn(form_element, "focus");

            modal.show();

            expect(focus).toHaveBeenCalled();
        });

        it(`focuses the first data-dismiss element if no other form element was found when modal opens`, () => {
            const data_dismiss_element = doc.createElement("div");
            data_dismiss_element.setAttribute("data-dismiss", "modal");
            modal_element.appendChild(data_dismiss_element);
            const focus = vi.spyOn(data_dismiss_element, "focus");

            modal.show();

            expect(focus).toHaveBeenCalled();
        });
    });

    describe(`setPreviousActiveElement()`, () => {
        let modal: Modal;
        beforeEach(() => {
            document.body.innerHTML = "";

            const form_element = document.createElement("input");
            modal_element = doc.createElement("div");
            modal_element.appendChild(form_element);
            modal = createModal(document, modal_element);

            document.body.append(modal_element);
        });
        afterEach(() => {
            modal.destroy();
        });

        it("focuses the previous active element when modal closes", () => {
            const button = document.createElement("button");
            document.body.append(button);

            button.focus();
            modal.show();
            modal.hide();

            expect(document.activeElement).toBe(button);
        });

        it("focuses the trigger of the dropdown if previous active element was in dropdown", () => {
            const dropdown_item = document.createElement("button");
            dropdown_item.classList.add("tlp-dropdown-menu-item");
            const dropdown_menu = document.createElement("div");
            dropdown_menu.dataset.dropdown = "menu";
            const dropdown_trigger = document.createElement("button");
            dropdown_trigger.dataset.dropdown = "trigger";
            const dropdown = document.createElement("div");
            dropdown_menu.append(dropdown_item);
            dropdown.append(dropdown_trigger, dropdown_menu);
            document.body.append(dropdown);

            dropdown_item.focus();
            modal.show();
            modal.hide();

            expect(document.activeElement).toBe(dropdown_trigger);
        });

        it("focuses the trigger of the dropdown if previous active element was in dropdown menu outside of dropdown element", () => {
            const dropdown_item = document.createElement("button");
            dropdown_item.classList.add("tlp-dropdown-menu-item");
            const dropdown_menu = document.createElement("div");
            dropdown_menu.dataset.dropdown = "menu";
            dropdown_menu.dataset.triggeredBy = "lorem-ipsum";
            const dropdown_trigger = document.createElement("button");
            dropdown_trigger.dataset.dropdown = "trigger";
            dropdown_trigger.id = "lorem-ipsum";
            const dropdown = document.createElement("div");
            dropdown_menu.append(dropdown_item);
            dropdown.append(dropdown_trigger);
            document.body.append(dropdown, dropdown_menu);

            dropdown_item.focus();
            modal.show();
            modal.hide();

            expect(document.activeElement).toBe(dropdown_trigger);
        });
    });

    describe(`Closing the modal with clicks`, () => {
        let modal: Modal, dismiss_on_backdrop_click: boolean, closing_element: HTMLElement;
        beforeEach(() => {
            dismiss_on_backdrop_click = true;
            closing_element = doc.createElement("span");
            closing_element.dataset.dismiss = "modal";
            modal_element.append(closing_element);
        });
        afterEach(() => {
            modal.destroy();
        });

        const showModal = (): void => {
            modal = createModal(doc, modal_element, { dismiss_on_backdrop_click });
            modal.show();
        };

        it(`when I click on the backdrop element, it will hide the modal`, () => {
            showModal();
            selectOrThrow(doc, `#${BACKDROP_ID}`).dispatchEvent(new Event("click"));
            expectTheModalToBeHidden(modal_element);
        });

        it(`when I click on the backdrop element,
            it will dispatch the "will hide" event on the modal
            and if it is prevented, the modal will NOT be hidden`, () => {
            showModal();
            let event_dispatched = false;
            modal.addEventListener(EVENT_TLP_MODAL_WILL_HIDE, (event) => {
                event_dispatched = true;
                event.preventDefault();
            });
            selectOrThrow(doc, `#${BACKDROP_ID}`).dispatchEvent(new Event("click"));

            expect(event_dispatched).toBe(true);
            expectTheModalToBeShown(modal_element);
        });

        it(`when I click on the backdrop element, it will NOT hide the modal if dismiss_on_backdrop_click is false`, () => {
            dismiss_on_backdrop_click = false;
            showModal();
            selectOrThrow(doc, `#${BACKDROP_ID}`).dispatchEvent(new Event("click"));
            expectTheModalToBeShown(modal_element);
        });

        it(`when I click on a [data-dismiss=modal] element, it will hide the modal`, () => {
            showModal();
            closing_element.dispatchEvent(new Event("click"));
            expectTheModalToBeHidden(modal_element);
        });

        it(`when I click on a [data-dismiss=modal] element,
            it will dispatch the "will hide" event on the modal
            and if it is prevented, the modal will NOT be hidden`, () => {
            showModal();
            let event_dispatched = false;
            modal.addEventListener(EVENT_TLP_MODAL_WILL_HIDE, (event) => {
                event_dispatched = true;
                event.preventDefault();
            });
            closing_element.dispatchEvent(new Event("click"));

            expect(event_dispatched).toBe(true);
            expectTheModalToBeShown(modal_element);
        });
    });

    describe(`removeEventListener`, () => {
        it(`removes a listener from the modal`, () => {
            const modal = createModal(doc, modal_element);
            const listener = vi.fn();
            modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, listener);
            modal.show();

            modal.removeEventListener(EVENT_TLP_MODAL_HIDDEN, listener);
            modal.hide();
            expect(listener).not.toHaveBeenCalled();

            modal.destroy();
        });
    });

    describe(`when the modal has the keyboard option`, () => {
        let modal: Modal;
        beforeEach(() => {
            modal = createModal(doc, modal_element, { keyboard: true });
            modal.show();
        });
        afterEach(() => {
            modal.destroy();
        });

        it(`and I hit a key that isn't Escape, nothing happens`, () => {
            doc.body.dispatchEvent(new KeyboardEvent("keyup", { key: "A", bubbles: true }));

            expectTheModalToBeShown(modal_element);
        });

        it(`and given the modal was hidden, when I hit the Escape key, nothing happens`, () => {
            modal.hide();
            simulateEscapeKey(doc.body);

            expectTheModalToBeHidden(modal_element);
        });

        it(`and I hit the Escape key, the modal will be hidden`, () => {
            simulateEscapeKey(doc.body);

            expectTheModalToBeHidden(modal_element);
        });

        it(`and I hit the Escape key,
            it will dispatch the "will hide" event on the modal
            and if it is prevented, the modal will NOT be hidden`, () => {
            let event_dispatched = false;
            modal.addEventListener(EVENT_TLP_MODAL_WILL_HIDE, (event) => {
                event_dispatched = true;
                event.preventDefault();
            });
            simulateEscapeKey(doc.body);

            expect(event_dispatched).toBe(true);
            expectTheModalToBeShown(modal_element);
        });

        it(`when it is destroyed, the modal will remove its keyup listener`, () => {
            const removeEventListener = vi.spyOn(doc, "removeEventListener");
            modal.destroy();

            expect(removeEventListener).toHaveBeenCalledWith("keyup", expect.anything());
        });

        describe(`hitting the Escape key in a form field`, () => {
            beforeEach(() => {
                document.body.innerHTML = ""; //We need to use document here so we can use focus()
                modal = createModal(document, modal_element, { keyboard: true });
                modal.show();
            });
            afterEach(() => {
                modal.destroy();
            });

            it.each(["input", "textarea", "select"])(
                `will 'unfocus' it if it is an %p element without closing the modal.`,
                (form_element) => {
                    const element = document.createElement(form_element);
                    document.body.append(element);
                    element.focus();
                    simulateEscapeKey(element);

                    expectTheModalToBeShown(modal_element);
                    expect(element).not.toBe(document.activeElement);

                    element.remove();
                },
            );
        });
    });
});

function expectTheModalToBeShown(modal_element: HTMLElement): void {
    expect(modal_element.classList.contains(MODAL_SHOWN_CLASS_NAME)).toBe(true);
}

function expectTheModalToBeHidden(modal_element: HTMLElement): void {
    expect(modal_element.classList.contains(MODAL_SHOWN_CLASS_NAME)).toBe(false);
}

function simulateEscapeKey(element: HTMLElement): void {
    element.dispatchEvent(new KeyboardEvent("keyup", { key: "Escape", bubbles: true }));
}
