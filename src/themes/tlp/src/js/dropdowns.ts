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

import { findClosestElement } from "./dom-walker";

const TRANSITION_DURATION = 75;

export const EVENT_TLP_DROPDOWN_SHOWN = "tlp-dropdown-shown";
export const EVENT_TLP_DROPDOWN_HIDDEN = "tlp-dropdown-hidden";
type DropdownEventType = "tlp-dropdown-shown" | "tlp-dropdown-hidden";

export const DROPDOWN_MENU_CLASS_NAME = "tlp-dropdown-menu";
export const DROPDOWN_SHOWN_CLASS_NAME = "tlp-dropdown-shown";

export interface DropdownOptions {
    keyboard?: boolean;
    dropdown_menu?: Element;
}
export type DropdownEventHandler = (event: CustomEvent<{ target: HTMLElement }>) => void;

interface EventListener {
    type: DropdownEventType;
    eventHandler: DropdownEventHandler;
}

export const createDropdown = (
    doc: Document,
    trigger: Element,
    options?: DropdownOptions
): Dropdown => new Dropdown(doc, trigger, options);

const isEscapeKeyForInternetExplorer11 = (key: string): boolean => key === "Esc";

export class Dropdown {
    private readonly doc: Document;
    private readonly trigger: Element;
    private readonly dropdown_menu: HTMLElement;
    private readonly keyboard: boolean;
    is_shown: boolean;
    private readonly shown_event: CustomEvent<{ target: HTMLElement }>;
    private readonly hidden_event: CustomEvent<{ target: HTMLElement }>;
    private readonly event_listeners: EventListener[];

    constructor(doc: Document, trigger: Element, options: DropdownOptions = { keyboard: true }) {
        this.doc = doc;
        this.trigger = trigger;

        const { keyboard = true, dropdown_menu = this.getDropdownMenu() } = options;
        if (dropdown_menu === null) {
            throw new Error("Could not find .tlp-dropdown-menu");
        }
        if (!(dropdown_menu instanceof HTMLElement)) {
            throw new Error("Dropdown menu must be an HTML element");
        }
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

    getDropdownMenu(): HTMLElement | null {
        let dropdown_menu = this.trigger.nextSibling;

        while (
            dropdown_menu &&
            (!(dropdown_menu instanceof HTMLElement) ||
                !dropdown_menu.classList.contains(DROPDOWN_MENU_CLASS_NAME))
        ) {
            dropdown_menu = dropdown_menu.nextSibling;
        }

        return dropdown_menu;
    }

    toggle(): void {
        this.is_shown ? this.hide() : this.show();
    }

    show(): void {
        this.dropdown_menu.classList.add(DROPDOWN_SHOWN_CLASS_NAME);
        this.is_shown = true;
        this.reflow();

        this.dispatchEvent(this.shown_event);
    }

    hide(): void {
        this.dropdown_menu.classList.remove(DROPDOWN_SHOWN_CLASS_NAME);
        this.is_shown = false;
        this.reflow();

        setTimeout(() => {
            this.dispatchEvent(this.hidden_event);
        }, TRANSITION_DURATION);
    }

    reflow(): void {
        this.dropdown_menu.offsetHeight;
    }

    listenOpenEvents(): void {
        this.trigger.addEventListener("click", (event) => {
            event.preventDefault();
            this.toggle();
        });
    }

    listenCloseEvents(): void {
        this.doc.addEventListener("click", (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }
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

                if (isInputElement(event.target)) {
                    return;
                }

                if (this.is_shown) {
                    this.hide();
                }
            });
        }
    }

    addEventListener(type: DropdownEventType, eventHandler: DropdownEventHandler): void {
        const listener = { type, eventHandler };
        this.event_listeners.push(listener);
    }

    removeEventListener(type: DropdownEventType, eventHandler: DropdownEventHandler): void {
        for (const [index, listener] of this.event_listeners.entries()) {
            if (listener.type === type && listener.eventHandler === eventHandler) {
                this.event_listeners.slice(index, 1);
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
}

function isInputElement(eventTarget: EventTarget | null): boolean {
    if (!(eventTarget instanceof Element)) {
        return false;
    }
    const tag_name = eventTarget.tagName.toUpperCase();
    return tag_name === "INPUT" || tag_name === "SELECT" || tag_name === "TEXTAREA";
}
