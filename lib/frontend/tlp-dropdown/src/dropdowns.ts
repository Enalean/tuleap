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

import { autoUpdate, computePosition, flip, offset, shift } from "@floating-ui/dom";
import type { Placement } from "@floating-ui/dom";

const TRANSITION_DURATION = 75;

export const EVENT_TLP_DROPDOWN_SHOWN = "tlp-dropdown-shown";
export const EVENT_TLP_DROPDOWN_HIDDEN = "tlp-dropdown-hidden";
type DropdownEventType = "tlp-dropdown-shown" | "tlp-dropdown-hidden";

export const DROPDOWN_MENU_CLASS_NAME = "tlp-dropdown-menu";
export const DROPDOWN_SHOWN_CLASS_NAME = "tlp-dropdown-shown";

export const TRIGGER_CLICK = "click";
export const TRIGGER_HOVER_AND_CLICK = "hover-and-click";
const TriggerType = [TRIGGER_CLICK, TRIGGER_HOVER_AND_CLICK] as const;
type TriggerType = (typeof TriggerType)[number];

export interface DropdownOptions {
    keyboard?: boolean;
    trigger?: TriggerType;
    dropdown_menu?: Element;
    anchor?: Element;
}
export type DropdownEventHandler = (event: CustomEvent<{ target: HTMLElement }>) => void;

interface EventListener {
    type: DropdownEventType;
    eventHandler: DropdownEventHandler;
}

export const createDropdown = (
    doc: Document,
    trigger: Element,
    options?: DropdownOptions,
): Dropdown => new Dropdown(doc, trigger, options);

export class Dropdown {
    private readonly doc: Document;
    readonly trigger_element: Element;
    private readonly anchor_element: Element;
    readonly dropdown_menu: HTMLElement;
    private readonly keyboard: boolean;
    is_shown: boolean;
    private readonly shown_event: CustomEvent<{ target: HTMLElement }>;
    private readonly hidden_event: CustomEvent<{ target: HTMLElement }>;
    private readonly event_listeners: EventListener[];
    private readonly document_event_handler: EventListenerObject;
    private readonly trigger_event_handler: EventListenerObject;
    private readonly cleanup: () => void;

    constructor(doc: Document, trigger_element: Element, options: Partial<DropdownOptions> = {}) {
        this.doc = doc;
        this.trigger_element = trigger_element;
        this.trigger_element.setAttribute("data-dropdown", "trigger");

        const {
            keyboard = true,
            dropdown_menu = this.getDropdownMenu(),
            trigger = TRIGGER_CLICK,
            anchor = null,
        } = options;
        if (dropdown_menu === null) {
            throw new Error("Could not find .tlp-dropdown-menu");
        }
        if (!(dropdown_menu instanceof HTMLElement)) {
            throw new Error("Dropdown menu must be an HTML element");
        }
        this.dropdown_menu = dropdown_menu;
        this.anchor_element = anchor || this.trigger_element;
        this.is_shown = false;
        this.cleanup = autoUpdate(
            this.anchor_element,
            this.dropdown_menu,
            this.updatePositionOfMenu.bind(this),
        );
        this.dropdown_menu.setAttribute("data-dropdown", "menu");
        this.keyboard = keyboard;
        this.shown_event = new CustomEvent(EVENT_TLP_DROPDOWN_SHOWN, {
            detail: { target: this.dropdown_menu },
        });
        this.hidden_event = new CustomEvent(EVENT_TLP_DROPDOWN_HIDDEN, {
            detail: { target: this.dropdown_menu },
        });
        this.event_listeners = [];

        this.document_event_handler = DocumentEventHandler(this);
        this.trigger_event_handler = TriggerEventHandler(this);
        this.listenOpenEvents(trigger);
        this.listenCloseEvents(trigger);
    }

    async updatePositionOfMenu(): Promise<void> {
        if (!this.is_shown) {
            return;
        }

        const side = this.dropdown_menu.classList.contains("tlp-dropdown-menu-top")
            ? "top"
            : this.dropdown_menu.classList.contains("tlp-dropdown-menu-side")
              ? "right"
              : "bottom";
        const alignment = "start";
        const wanted_placement: Placement = `${side}-${alignment}`;

        const { x, y, placement } = await computePosition(this.anchor_element, this.dropdown_menu, {
            placement: wanted_placement,
            middleware: [
                offset(({ rects, placement }) => {
                    const mainAxis =
                        (placement.indexOf("top") === 0 ? rects.reference.height / 2 : 0) - 4;

                    if (this.dropdown_menu.classList.contains("tlp-dropdown-menu-side")) {
                        if (placement.indexOf("end") > 0) {
                            return { mainAxis, crossAxis: 8 };
                        }

                        return { mainAxis, crossAxis: -8 };
                    }

                    return mainAxis;
                }),
                flip({ fallbackStrategy: "initialPlacement" }),
                shift({ padding: 16 }),
            ],
        });

        Object.assign(this.dropdown_menu.style, {
            left: `${x}px`,
            top: `${y}px`,
        });
        this.dropdown_menu.dataset.placement = placement;
    }

    getDropdownMenu(): HTMLElement | null {
        let dropdown_menu = this.trigger_element.nextSibling;

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
        if (this.is_shown) {
            this.hide();
            return;
        }
        this.show();
    }

    async show(): Promise<void> {
        this.dropdown_menu.classList.add(DROPDOWN_SHOWN_CLASS_NAME);
        this.is_shown = true;
        await this.updatePositionOfMenu();

        this.dispatchEvent(this.shown_event);
        this.dropdown_menu.dispatchEvent(this.shown_event);
    }

    hide(): void {
        this.dropdown_menu.classList.remove(DROPDOWN_SHOWN_CLASS_NAME);
        this.is_shown = false;
        this.cleanup();

        setTimeout(() => {
            this.dispatchEvent(this.hidden_event);
            this.dropdown_menu.dispatchEvent(this.hidden_event);
        }, TRANSITION_DURATION);
    }

    listenOpenEvents(trigger: TriggerType): void {
        this.trigger_element.addEventListener("click", this.trigger_event_handler);

        if (trigger === TRIGGER_HOVER_AND_CLICK) {
            this.trigger_element.addEventListener("mouseenter", this.trigger_event_handler);
        }
    }

    listenCloseEvents(trigger: TriggerType): void {
        this.doc.addEventListener("click", this.document_event_handler);

        if (trigger === TRIGGER_HOVER_AND_CLICK) {
            this.trigger_element.addEventListener("mouseleave", this.trigger_event_handler);
        }
        if (this.keyboard) {
            this.doc.addEventListener("keyup", this.document_event_handler);
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

    destroy(): void {
        this.trigger_element.removeEventListener("mouseleave", this.trigger_event_handler);
        this.trigger_element.removeEventListener("mouseenter", this.trigger_event_handler);
        this.trigger_element.removeEventListener("click", this.trigger_event_handler);
        this.doc.removeEventListener("click", this.document_event_handler);
        this.doc.removeEventListener("keyup", this.document_event_handler);
    }
}

function TriggerEventHandler(dropdown: Dropdown): EventListenerObject {
    const handleClick = (event: Event): void => {
        event.preventDefault();
        dropdown.toggle();
    };

    const handleMouseEnter = (): void => {
        if (dropdown.is_shown) {
            return;
        }
        dropdown.show();
    };

    const handleMouseLeave = (): void => {
        if (dropdown.is_shown) {
            dropdown.hide();
        }
    };

    return {
        handleEvent(event: Event): void {
            if (event.type === "mouseleave") {
                handleMouseLeave();
                return;
            }
            if (event.type === "mouseenter") {
                handleMouseEnter();
                return;
            }
            if (event.type === "click") {
                handleClick(event);
            }
        },
    };
}

function DocumentEventHandler(dropdown: Dropdown): EventListenerObject {
    const handleClick = (event: Event): void => {
        if (!(event.target instanceof Element)) {
            return;
        }
        if (
            dropdown.is_shown &&
            !dropdown.dropdown_menu.contains(event.target) &&
            !dropdown.trigger_element.contains(event.target)
        ) {
            dropdown.hide();
        }
    };

    const handleKeyUp = (event: KeyboardEvent): void => {
        if (event.key !== "Escape") {
            return;
        }

        if (isInputElement(event.target)) {
            return;
        }

        if (dropdown.is_shown) {
            dropdown.hide();
        }
    };

    return {
        handleEvent(event: Event): void {
            if (event.type === "click") {
                handleClick(event);
                return;
            }
            if (event.type === "keyup" && event instanceof KeyboardEvent) {
                handleKeyUp(event);
            }
        },
    };
}

function isInputElement(eventTarget: EventTarget | null): boolean {
    if (!(eventTarget instanceof Element)) {
        return false;
    }
    const tag_name = eventTarget.tagName.toUpperCase();
    return tag_name === "INPUT" || tag_name === "SELECT" || tag_name === "TEXTAREA";
}
