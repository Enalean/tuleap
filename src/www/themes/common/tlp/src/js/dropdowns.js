/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
const ESCAPE_CODE = 27;

const EVENT_TLP_DROPDOWN_SHOWN = "tlp-dropdown-shown";
const EVENT_TLP_DROPDOWN_HIDDEN = "tlp-dropdown-hidden";

const CLASS_TLP_DROPDOWN_MENU = "tlp-dropdown-menu";
const CLASS_TLP_DROPDOWN_SHOWN = "tlp-dropdown-shown";

export default (trigger, options) => new Dropdown(trigger, options);

class Dropdown {
    constructor(trigger, options = { keyboard: true }) {
        this.trigger = trigger;

        let { keyboard = true, dropdown_menu = this.getDropdownMenu() } = options;

        this.body_element = document.body;
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
                !dropdown_menu.classList.contains(CLASS_TLP_DROPDOWN_MENU))
        ) {
            dropdown_menu = dropdown_menu.nextSibling;
        }

        return dropdown_menu;
    }

    toggle() {
        this.is_shown ? this.hide() : this.show();
    }

    show() {
        this.dropdown_menu.classList.add(CLASS_TLP_DROPDOWN_SHOWN);
        this.is_shown = true;
        this.reflow();

        this.dispatchEvent(this.shown_event);
    }

    hide() {
        this.dropdown_menu.classList.remove(CLASS_TLP_DROPDOWN_SHOWN);
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
        document.addEventListener("click", (event) => {
            if (
                this.is_shown &&
                !findClosestElement(event.target, this.dropdown_menu) &&
                !findClosestElement(event.target, this.trigger)
            ) {
                this.hide();
            }
        });

        if (this.keyboard) {
            document.addEventListener("keyup", (event) => {
                if (event.keyCode !== ESCAPE_CODE) {
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
