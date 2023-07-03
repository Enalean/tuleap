/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

export const EVENT_TLP_MODAL_SHOWN = "tlp-modal-shown";
export const EVENT_TLP_MODAL_HIDDEN = "tlp-modal-hidden";
export const EVENT_TLP_MODAL_WILL_HIDE = "tlp-modal-will-hide";
type ModalEventType = "tlp-modal-shown" | "tlp-modal-hidden" | "tlp-modal-will-hide";

export const BACKDROP_ID = "tlp-modal-backdrop";
export const BACKDROP_SHOWN_CLASS_NAME = "tlp-modal-backdrop-shown";
export const MODAL_SHOWN_CLASS_NAME = "tlp-modal-shown";
const DISMISS_SELECTOR = '[data-dismiss="modal"]';

export interface ModalOptions {
    keyboard?: boolean;
    destroy_on_hide?: boolean;
    dismiss_on_backdrop_click?: boolean;
}

type ModalEventListener = (event: CustomEvent<{ target: HTMLElement }>) => void;

interface EventListener {
    type: ModalEventType;
    eventHandler: ModalEventListener;
}

export const createModal = (doc: Document, element: Element, options?: ModalOptions): Modal =>
    new Modal(doc, element, options);

const getDropdownTrigger = (dropdown_menu: HTMLElement): HTMLElement => {
    if (dropdown_menu.dataset.triggeredBy) {
        const dropdown_trigger = dropdown_menu.ownerDocument.getElementById(
            dropdown_menu.dataset.triggeredBy
        );
        if (!dropdown_trigger) {
            throw new Error("Dropdown trigger should exist");
        }

        return dropdown_trigger;
    }

    let dropdown_trigger = dropdown_menu.previousSibling;
    while (
        dropdown_trigger &&
        (!(dropdown_trigger instanceof HTMLElement) ||
            dropdown_trigger.getAttribute("data-dropdown") !== "trigger")
    ) {
        dropdown_trigger = dropdown_trigger.previousSibling;
    }

    if (!dropdown_trigger) {
        throw new Error("Dropdown trigger should exist");
    }
    return dropdown_trigger;
};

export class Modal {
    private readonly doc: Document;
    private readonly element: Element;
    is_shown: boolean;
    private readonly options: ModalOptions;
    private readonly event_listeners: EventListener[] = [];
    private backdrop_element: HTMLDivElement | null = null;
    private readonly shown_event: CustomEvent<{ target: Element }>;
    private readonly hidden_event: CustomEvent<{ target: Element }>;
    private readonly event_handler: EventListenerObject;
    private previous_active_element: HTMLElement | null = null;

    constructor(doc: Document, element: Element, options: ModalOptions = { keyboard: true }) {
        const {
            keyboard = true,
            destroy_on_hide = false,
            dismiss_on_backdrop_click = true,
        } = options;
        this.doc = doc;
        this.element = element;
        this.is_shown = false;
        this.options = {
            keyboard,
            destroy_on_hide,
            dismiss_on_backdrop_click,
        };
        this.shown_event = new CustomEvent(EVENT_TLP_MODAL_SHOWN, {
            detail: { target: this.element },
        });
        this.hidden_event = new CustomEvent(EVENT_TLP_MODAL_HIDDEN, {
            detail: { target: this.element },
        });
        this.event_handler = ModalEventHandler(this);
        this.listenCloseEvents();
    }

    toggle(): void {
        this.is_shown ? this.hide() : this.show();
    }

    show(): void {
        this.element.classList.add(MODAL_SHOWN_CLASS_NAME);

        this.is_shown = true;
        this.setPreviousActiveElement();
        this.bringFocusInsideModal();
        this.addBackdrop();
        this.dispatchEvent(this.shown_event);
        this.doc.dispatchEvent(this.shown_event);
    }

    private bringFocusInsideModal(): void {
        const custom_focused_element = this.element.querySelector("[data-modal-focus]");
        if (custom_focused_element instanceof HTMLElement) {
            custom_focused_element.focus();
            return;
        }

        const first_form_element = this.element.querySelector(
            `input:enabled:not([type='hidden']),
            textarea:enabled:not([type='hidden']),
            select:enabled:not([type='hidden']),
            button:enabled:not([data-dismiss]),
            [href]`
        );
        if (first_form_element instanceof HTMLElement) {
            first_form_element.focus();
            return;
        }

        const dismiss_button = this.element.querySelector(DISMISS_SELECTOR);
        if (dismiss_button instanceof HTMLElement) {
            dismiss_button.focus();
        }
    }

    hide(): void {
        this.element.classList.remove(MODAL_SHOWN_CLASS_NAME);
        this.removeBackdrop();

        if (this.options.destroy_on_hide) {
            this.destroy();
        }

        this.is_shown = false;

        this.dispatchEvent(this.hidden_event);
        this.doc.dispatchEvent(this.hidden_event);

        if (this.previous_active_element) {
            this.previous_active_element.focus();
        }
    }

    addBackdrop(): void {
        this.backdrop_element = this.doc.createElement("div");
        this.backdrop_element.id = BACKDROP_ID;
        this.element.after(this.backdrop_element);

        this.backdrop_element.classList.add(BACKDROP_SHOWN_CLASS_NAME);
        if (this.options.dismiss_on_backdrop_click) {
            this.backdrop_element.addEventListener("click", this.event_handler);
        }
    }

    removeBackdrop(): void {
        this.backdrop_element?.classList.remove(BACKDROP_SHOWN_CLASS_NAME);

        this.backdrop_element?.remove();
    }

    listenCloseEvents(): void {
        this.close_elements.forEach((close_element) => {
            close_element.addEventListener("click", this.event_handler);
        });

        if (this.options.keyboard) {
            this.doc.addEventListener("keyup", this.event_handler);
        }
    }

    destroy(): void {
        this.close_elements.forEach((close_element) => {
            close_element.removeEventListener("click", this.event_handler);
        });

        if (this.backdrop_element) {
            this.backdrop_element.remove();
        }

        if (this.options.keyboard) {
            this.doc.removeEventListener("keyup", this.event_handler);
        }
    }

    addEventListener(type: ModalEventType, eventHandler: ModalEventListener): void {
        const listener = { type, eventHandler };
        this.event_listeners.push(listener);
    }

    removeEventListener(type: ModalEventType, eventHandler: ModalEventListener): void {
        for (const [index, listener] of this.event_listeners.entries()) {
            if (listener.type === type && listener.eventHandler === eventHandler) {
                this.event_listeners.splice(index, 1);
            }
        }
    }

    dispatchEvent(event: CustomEvent): void {
        for (const listener of this.event_listeners) {
            if (event.type === listener.type) {
                listener.eventHandler(event);
            }
        }
    }

    get close_elements(): Element[] {
        const children = this.element.querySelectorAll(DISMISS_SELECTOR);
        return [...children];
    }

    private setPreviousActiveElement(): void {
        const active_element = this.doc.activeElement;
        if (!(active_element instanceof HTMLElement)) {
            return;
        }

        const dropdown_menu = active_element.closest("[data-dropdown=menu]");
        if (dropdown_menu instanceof HTMLElement) {
            this.previous_active_element = getDropdownTrigger(dropdown_menu);
            return;
        }
        this.previous_active_element = active_element;
    }
}

function ModalEventHandler(modal: Modal): EventListenerObject {
    const hideModal = (): void => {
        const event = new CustomEvent(EVENT_TLP_MODAL_WILL_HIDE, { cancelable: true });
        modal.dispatchEvent(event);
        if (event.defaultPrevented) {
            return;
        }
        modal.hide();
    };

    const handleKeyUp = (event: KeyboardEvent): void => {
        if (event.key !== "Escape") {
            return;
        }

        if (
            event.target instanceof HTMLInputElement ||
            event.target instanceof HTMLSelectElement ||
            event.target instanceof HTMLTextAreaElement
        ) {
            event.target.blur();
            return;
        }

        if (modal.is_shown) {
            hideModal();
        }
    };

    return {
        handleEvent(event): void {
            if (event.type === "click") {
                hideModal();
                return;
            }
            if (event.type === "keyup" && event instanceof KeyboardEvent) {
                handleKeyUp(event);
            }
        },
    };
}
