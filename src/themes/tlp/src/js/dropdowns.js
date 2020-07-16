/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { findClosestElement } from "./dom-walker.js";

const TRANSITION_DURATION = 75;

export const EVENT_TLP_DROPDOWN_SHOWN = "tlp-dropdown-shown";
export const EVENT_TLP_DROPDOWN_HIDDEN = "tlp-dropdown-hidden";

export const DROPDOWN_MENU_CLASS_NAME = "tlp-dropdown-menu";
export const DROPDOWN_SHOWN_CLASS_NAME = "tlp-dropdown-shown";

export const dropdown = (doc, trigger, options) => new Dropdown(doc, trigger, options);

class Dropdown {
    constructor(doc, trigger, options = { keyboard: true }) {
        this.doc = doc;
        this.trigger = trigger;

        let { keyboard = true, dropdown_menu = this.getDropdownMenu() } = options;

        this.dropdown_menu = dropdown_menu;
        this.is_shown = false;
        this.keyboard = keyboard;
        this.shown_event = new CustomEvent(EVENT_TLP_DROPDOWN_SHOWN, {
            detail: { target: this.dropdown_menu },
        });
        this.hidden_event = new CustomEvent(EVENT_TLP_DROPDOWN_HIDDEN, {
            detail: { target: this.dropdown_menu },
        });
        this.event_listeners = [];

        this.listenOpenEvents();
        this.listenCloseEvents();
    }

    getDropdownMenu() {
        let dropdown_menu = this.trigger.nextSibling;

        while (
            dropdown_menu &&
            (dropdown_menu.nodeType !== Node.ELEMENT_NODE ||
                !dropdown_menu.classList.contains(DROPDOWN_MENU_CLASS_NAME))
        ) {
            dropdown_menu = dropdown_menu.nextSibling;
        }

        return dropdown_menu;
    }

    toggle() {
        this.is_shown ? this.hide() : this.show();
    }

    show() {
        this.dropdown_menu.classList.add(DROPDOWN_SHOWN_CLASS_NAME);
        this.is_shown = true;
        this.reflow();

        this.dispatchEvent(this.shown_event);
    }

    hide() {
        this.dropdown_menu.classList.remove(DROPDOWN_SHOWN_CLASS_NAME);
        this.is_shown = false;
        this.reflow();

        setTimeout(() => {
            this.dispatchEvent(this.hidden_event);
        }, TRANSITION_DURATION);
    }

    reflow() {
        this.dropdown_menu.offsetHeight;
    }

    listenOpenEvents() {
        this.trigger.addEventListener("click", (event) => {
            event.preventDefault();
            this.toggle();
        });
    }

    listenCloseEvents() {
        this.doc.addEventListener("click", (event) => {
            if (
                this.is_shown &&
                !findClosestElement(this.doc, event.target, this.dropdown_menu) &&
                !findClosestElement(this.doc, event.target, this.trigger)
            ) {
                this.hide();
            }
        });

        if (this.keyboard) {
            this.doc.addEventListener("keyup", (event) => {
                if (event.key !== "Escape" && !isEscapeKeyForInternetExplorer11(event.key)) {
                    return;
                }

                let tag_name = event.target.tagName.toUpperCase();
                if (tag_name === "INPUT" || tag_name === "SELECT" || tag_name === "TEXTAREA") {
                    return;
                }

                if (this.is_shown) {
                    this.hide();
                }
            });
        }
    }

    addEventListener(type, eventHandler) {
        let listener = { type, eventHandler };
        this.event_listeners.push(listener);
    }

    removeEventListener(type, eventHandler) {
        for (let [index, listener] of this.event_listeners.entries()) {
            if (listener.type === type && listener.eventHandler === eventHandler) {
                this.event_listeners.slice(index, 1);
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
}

const isEscapeKeyForInternetExplorer11 = (key) => key === "Esc";
