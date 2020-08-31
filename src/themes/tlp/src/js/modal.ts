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

const TRANSITION_DURATION = 300;

export const EVENT_TLP_MODAL_SHOWN = "tlp-modal-shown";
export const EVENT_TLP_MODAL_HIDDEN = "tlp-modal-hidden";
type ModalEventType = "tlp-modal-shown" | "tlp-modal-hidden";

export const BACKDROP_ID = "tlp-modal-backdrop";
export const BACKDROP_SHOWN_CLASS_NAME = "tlp-modal-backdrop-shown";
export const MODAL_DISPLAY_CLASS_NAME = "tlp-modal-display";
export const MODAL_SHOWN_CLASS_NAME = "tlp-modal-shown";

export interface ModalOptions {
    keyboard?: boolean;
    destroy_on_hide?: boolean;
}

export type ModalEventListener = (event: CustomEvent<{ target: HTMLElement }>) => void;

interface EventListener {
    type: ModalEventType;
    eventHandler: ModalEventListener;
}

export const createModal = (doc: Document, element: Element, options?: ModalOptions): Modal =>
    new Modal(doc, element, options);

export class Modal {
    private readonly doc: Document;
    private readonly element: Element;
    is_shown: boolean;
    private readonly options: ModalOptions;
    private readonly event_listeners: EventListener[] = [];
    private backdrop_element: HTMLDivElement | null = null;
    private readonly shown_event: CustomEvent<{ target: Element }>;
    private readonly hidden_event: CustomEvent<{ target: Element }>;
    private readonly eventHandler: ModalEventHandler;

    constructor(doc: Document, element: Element, options: ModalOptions = { keyboard: true }) {
        const { keyboard = true, destroy_on_hide = false } = options;
        this.doc = doc;
        this.element = element;
        this.is_shown = false;
        this.options = {
            keyboard,
            destroy_on_hide,
        };
        this.shown_event = new CustomEvent(EVENT_TLP_MODAL_SHOWN, {
            detail: { target: this.element },
        });
        this.hidden_event = new CustomEvent(EVENT_TLP_MODAL_HIDDEN, {
            detail: { target: this.element },
        });
        this.eventHandler = new ModalEventHandler(this);
        this.listenCloseEvents();
    }

    toggle(): void {
        this.is_shown ? this.hide() : this.show();
    }

    show(): void {
        this.element.classList.add(MODAL_DISPLAY_CLASS_NAME);

        this.element.classList.add(MODAL_SHOWN_CLASS_NAME);
        this.is_shown = true;

        const input = this.element.querySelector("input");
        if (input) {
            input.focus();
        }

        this.addBackdrop();

        this.dispatchEvent(this.shown_event);
    }

    hide(): void {
        this.element.classList.remove(MODAL_SHOWN_CLASS_NAME);

        this.removeBackdrop();
        if (this.options.destroy_on_hide) {
            this.destroy();
        }

        setTimeout(() => {
            this.element.classList.remove(MODAL_DISPLAY_CLASS_NAME);
            this.is_shown = false;

            this.dispatchEvent(this.hidden_event);
        }, TRANSITION_DURATION);
    }

    addBackdrop(): void {
        this.backdrop_element = this.doc.createElement("div");
        this.backdrop_element.id = BACKDROP_ID;
        this.doc.body.appendChild(this.backdrop_element);

        this.backdrop_element.classList.add(BACKDROP_SHOWN_CLASS_NAME);
        this.backdrop_element.addEventListener("click", () => {
            this.hide();
        });
    }

    removeBackdrop(): void {
        this.backdrop_element?.classList.remove(BACKDROP_SHOWN_CLASS_NAME);

        setTimeout(() => {
            this.backdrop_element?.remove();
        }, TRANSITION_DURATION);
    }

    listenCloseEvents(): void {
        this.close_elements.forEach((close_element) => {
            close_element.addEventListener("click", this.eventHandler);
        });

        if (this.options.keyboard) {
            this.doc.addEventListener("keyup", this.eventHandler);
        }
    }

    destroy(): void {
        this.close_elements.forEach((close_element) => {
            close_element.removeEventListener("click", this.eventHandler);
        });

        if (this.backdrop_element) {
            this.backdrop_element.remove();
        }

        if (this.options.keyboard) {
            this.doc.removeEventListener("keyup", this.eventHandler);
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
        const children = this.element.querySelectorAll('[data-dismiss="modal"]');
        return [...children];
    }
}

const isEscapeKeyForInternetExplorer11 = (key: string): boolean => key === "Esc";

class ModalEventHandler implements EventListenerObject {
    private readonly modal: Modal;

    constructor(modal: Modal) {
        this.modal = modal;
    }

    handleEvent(event: Event): void {
        if (event.type === "click") {
            this.closeElementCallback();
        } else if (event.type === "keyup" && event instanceof KeyboardEvent) {
            this.keyupCallback(event);
        }
    }

    closeElementCallback(): void {
        this.modal.hide();
    }

    keyupCallback(event: KeyboardEvent): void {
        if (event.key !== "Escape" && !isEscapeKeyForInternetExplorer11(event.key)) {
            return;
        }

        if (isInputElement(event.target)) {
            return;
        }

        if (this.modal.is_shown) {
            this.modal.hide();
        }
    }
}

function isInputElement(eventTarget: EventTarget | null): boolean {
    if (!(eventTarget instanceof Element)) {
        return false;
    }
    const tag_name = eventTarget.tagName.toUpperCase();
    return tag_name === "INPUT" || tag_name === "SELECT" || tag_name === "TEXTAREA";
}
