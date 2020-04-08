/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
const ESCAPE_CODE = 27;

const EVENT_TLP_MODAL_SHOWN = "tlp-modal-shown";
const EVENT_TLP_MODAL_HIDDEN = "tlp-modal-hidden";

const CLASS_TLP_MODAL_SHOWN = "tlp-modal-shown";
const CLASS_TLP_MODAL_BACKDROP_SHOWN = "tlp-modal-backdrop-shown";
const CLASS_TLP_MODAL_DISPLAY = "tlp-modal-display";

const ID_TLP_MODAL_BACKDROP = "tlp-modal-backdrop";

export default (element, options) => new Modal(element, options);

class Modal {
    constructor(element, options = { keyboard: true }) {
        const { keyboard = true, destroy_on_hide = false } = options;
        this.body_element = document.body;
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
        this.event_listeners = [];
        this.eventHandler = new ModalEventHandler(this);
        this.listenCloseEvents();
    }

    toggle() {
        this.is_shown ? this.hide() : this.show();
    }

    show() {
        this.element.classList.add(CLASS_TLP_MODAL_DISPLAY);

        reflowElement(this.element);

        this.element.classList.add(CLASS_TLP_MODAL_SHOWN);
        this.is_shown = true;
        this.addBackdrop();

        this.dispatchEvent(this.shown_event);
    }

    hide() {
        this.element.classList.remove(CLASS_TLP_MODAL_SHOWN);

        reflowElement(this.element);

        this.removeBackdrop();
        if (this.options.destroy_on_hide) {
            this.destroy();
        }

        setTimeout(() => {
            this.element.classList.remove(CLASS_TLP_MODAL_DISPLAY);
            this.is_shown = false;

            this.dispatchEvent(this.hidden_event);
        }, TRANSITION_DURATION);
    }

    addBackdrop() {
        this.backdrop_element = document.createElement("div");
        this.backdrop_element.id = ID_TLP_MODAL_BACKDROP;
        this.body_element.appendChild(this.backdrop_element);

        reflowElement(this.backdrop_element);

        this.backdrop_element.classList.add(CLASS_TLP_MODAL_BACKDROP_SHOWN);
        this.backdrop_element.addEventListener("click", () => {
            this.hide();
        });
    }

    removeBackdrop() {
        this.backdrop_element.classList.remove(CLASS_TLP_MODAL_BACKDROP_SHOWN);

        setTimeout(() => {
            this.body_element.removeChild(this.backdrop_element);
        }, TRANSITION_DURATION);
    }

    listenCloseEvents() {
        this.close_elements.forEach((close_element) => {
            close_element.addEventListener("click", this.eventHandler);
        });

        if (this.options.keyboard) {
            document.addEventListener("keyup", this.eventHandler);
        }
    }

    destroy() {
        this.close_elements.forEach((close_element) => {
            close_element.removeEventListener("click", this.eventHandler);
        });

        if (this.options.keyboard) {
            document.removeEventListener("keyup", this.eventHandler);
        }
    }

    addEventListener(type, eventHandler) {
        let listener = { type, eventHandler };
        this.event_listeners.push(listener);
    }

    removeEventListener(type, eventHandler) {
        for (let [index, listener] of this.event_listeners.entries()) {
            if (listener.type === type && listener.eventHandler === eventHandler) {
                this.event_listeners.splice(index, 1);
            }
        }
    }

    dispatchEvent(event) {
        for (const listener of this.event_listeners) {
            if (event.type === listener.type) {
                listener.eventHandler(event);
            }
        }
    }

    get close_elements() {
        let children = this.element.querySelectorAll('[data-dismiss="modal"]');
        let close_elements = [];

        [].forEach.call(children, (child) => {
            close_elements.push(child);
        });

        return close_elements;
    }
}

function reflowElement(element) {
    element.offsetHeight;
}

class ModalEventHandler {
    constructor(modal) {
        this.modal = modal;
    }

    handleEvent(event) {
        if (event.type === "click") {
            this.closeElementCallback();
        } else if (event.type === "keyup") {
            this.keyupCallback(event);
        }
    }

    closeElementCallback() {
        this.modal.hide();
    }

    keyupCallback(event) {
        if (event.keyCode !== ESCAPE_CODE) {
            return;
        }

        let tag_name = event.target.tagName.toUpperCase();
        if (tag_name === "INPUT" || tag_name === "SELECT" || tag_name === "TEXTAREA") {
            return;
        }

        if (this.modal.is_shown) {
            this.modal.hide();
        }
    }
}
