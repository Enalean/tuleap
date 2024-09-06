/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { Placement } from "@floating-ui/dom";
import { computePosition, offset, shift, flip, autoUpdate, arrow } from "@floating-ui/dom";

export const POPOVER_SHOWN_CLASS_NAME = "tlp-popover-shown";
export const EVENT_TLP_POPOVER_HIDDEN = "tlp-popover-hidden";
export const EVENT_TLP_POPOVER_SHOWN = "tlp-popover-shown";

export const EVENT_POPOVER_FORCE_CLOSE = "tlp-popover-force-close";

const TIMEOUT_BEFORE_POPOVER_IS_HIDDEN_ON_MOUSEOUT_IN_MS = 200;

type Configuration = {
    readonly anchor: HTMLElement;
    readonly trigger: Trigger;
    readonly placement: Placement;
    readonly middleware: {
        readonly flip: {
            readonly fallbackPlacements?: Array<Placement>;
        };
        readonly offset: {
            readonly alignmentAxis?: number;
        };
    };
};

type Trigger = "click" | "hover" | "focus";

export type PopoverOptions = Partial<Configuration>;

export interface Popover {
    show(): void;
    hide(): void;
    destroy(): void;
}

export function createPopover(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
    options: PopoverOptions = {},
): Popover {
    const configuration = getConfiguration(popover_trigger, options);

    popover_content.dataset.popoverTrigger = configuration.trigger;

    const updatePositionOfContent = getUpdatePositionOfContentCallback(
        popover_content,
        configuration,
    );

    const cleanup = autoUpdate(configuration.anchor, popover_content, updatePositionOfContent);

    const dismiss_buttons = popover_content.querySelectorAll('[data-dismiss="popover"]');

    const listeners = buildListeners(
        doc,
        popover_trigger,
        popover_content,
        dismiss_buttons,
        configuration,
        updatePositionOfContent,
    );
    attachListeners(listeners);

    return {
        destroy: (): void => {
            destroyListeners(listeners);
            cleanup();
        },
        hide: (): void => {
            hidePopover(popover_content);
            cleanup();
        },
        show: (): void => {
            hideAllShownPopovers(doc);
            // In the current context, the opening is not performed by a DOM event
            // Thus, we need to wait until that all popovers have been closed before we can open it manually.
            setTimeout(() => showPopover(popover_content, updatePositionOfContent));
        },
    };
}

function getUpdatePositionOfContentCallback(
    popover_content: HTMLElement,
    configuration: Configuration,
): () => void {
    const middleware = [
        offset({
            mainAxis: 10,
            alignmentAxis: configuration.middleware.offset.alignmentAxis ?? -15,
        }),
        flip(configuration.middleware.flip),
        shift({ padding: 16 }),
    ];

    const arrow_element = popover_content.querySelector<HTMLElement>(".tlp-popover-arrow");
    if (arrow_element) {
        middleware.push(
            arrow({
                element: arrow_element,
                padding: 15,
            }),
        );
    }

    return (): void => {
        computePosition(configuration.anchor, popover_content, {
            placement: configuration.placement,
            middleware,
        }).then(({ x, y, placement, middlewareData }) => {
            Object.assign(popover_content.style, {
                left: `${x}px`,
                top: `${y}px`,
            });
            popover_content.dataset.popoverPlacement = placement;

            if (arrow_element && middlewareData && middlewareData.arrow) {
                const { x: arrow_x, y: arrow_y } = middlewareData.arrow;
                Object.assign(arrow_element.style, {
                    left: arrow_x !== undefined ? `${arrow_x}px` : "",
                    top: arrow_y !== undefined ? `${arrow_y}px` : "",
                });
            }
        });
    };
}

const allowed_placements = [
    "top",
    "top-start",
    "top-end",
    "right",
    "right-start",
    "right-end",
    "bottom",
    "bottom-start",
    "bottom-end",
    "left",
    "left-start",
    "left-end",
];

const isPlacement = (string_to_check: string): string_to_check is Placement =>
    allowed_placements.includes(string_to_check);

const isTrigger = (string_to_check: string | undefined): string_to_check is Trigger =>
    ["hover", "click", "focus"].includes(string_to_check ?? "");

function getTrigger(popover_trigger: HTMLElement, options: PopoverOptions): Trigger {
    if (options.trigger) {
        return options.trigger;
    }

    const dataset_trigger = popover_trigger.dataset.trigger;
    if (isTrigger(dataset_trigger)) {
        return dataset_trigger;
    }

    return "hover";
}

function getConfiguration(popover_trigger: HTMLElement, options: PopoverOptions): Configuration {
    const anchor = options.anchor ?? popover_trigger;
    const placement = options.placement ?? anchor.dataset.placement ?? "bottom";

    if (!isPlacement(placement)) {
        throw new Error(
            "Invalid placement received: " +
                placement +
                ". Allowed values are: " +
                allowed_placements.join(","),
        );
    }

    return {
        anchor,
        placement,
        trigger: getTrigger(popover_trigger, options),
        middleware: {
            flip: options.middleware?.flip ?? {},
            offset: options.middleware?.offset ?? {},
        },
    };
}

type EventType =
    | "click"
    | "mouseout"
    | "mouseenter"
    | "mouseleave"
    | "mouseover"
    | "keyup"
    | "keydown"
    | "focus"
    | "blur"
    | typeof EVENT_POPOVER_FORCE_CLOSE;

interface EventListener {
    element: EventTarget;
    type: EventType;
    handler: { (e: Event): unknown };
}

function buildListeners(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
    dismiss_buttons: NodeListOf<Element>,
    configuration: Configuration,
    updatePositionOfContent: () => void,
): EventListener[] {
    if (configuration.trigger === "focus") {
        return [
            buildFocusListener(doc, popover_trigger, popover_content, updatePositionOfContent),
            buildBlurListener(doc, popover_trigger, popover_content),
            buildForceClosePopover(doc, popover_content),
        ];
    }
    if (configuration.trigger === "hover") {
        return [
            buildMouseOverListener(doc, popover_trigger, popover_content, updatePositionOfContent),
            buildMouseOutListener(doc, popover_trigger, popover_content),
            buildMouseEnterListener(doc, popover_content),
            buildMouseLeaveListener(doc, popover_content),
            buildForceClosePopover(doc, popover_content),
        ];
    }
    if (configuration.trigger === "click") {
        const listeners = [
            buildTriggerClickListener(
                doc,
                popover_trigger,
                popover_content,
                updatePositionOfContent,
            ),
            buildDocumentClickListener(doc, popover_trigger, popover_content),
            buildEscapeListener(doc, popover_content),
            buildForceClosePopover(doc, popover_content),
        ];
        if (popover_trigger.role === "button" && popover_trigger.tabIndex >= 0) {
            listeners.push(
                buildTriggerAccessibleClickListener(
                    doc,
                    popover_trigger,
                    popover_content,
                    updatePositionOfContent,
                ),
            );
        }
        for (const dismiss of dismiss_buttons) {
            listeners.push(buildDismissClickListener(doc, dismiss));
        }
        return listeners;
    }

    return [];
}

function attachListeners(listeners: EventListener[]): void {
    for (const { element, type, handler } of listeners) {
        element.addEventListener(type, handler);
    }
}

function destroyListeners(listeners: EventListener[]): void {
    for (const { element, type, handler } of listeners) {
        element.removeEventListener(type, handler);
    }
}

function buildForceClosePopover(doc: Document, popover_content: HTMLElement): EventListener {
    return {
        element: doc,
        type: EVENT_POPOVER_FORCE_CLOSE,
        handler(): void {
            if (!popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME)) {
                return;
            }

            hidePopover(popover_content);
        },
    };
}

function buildFocusListener(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
    updatePositionOfContent: () => void,
): EventListener {
    return {
        element: popover_trigger,
        type: "focus",
        handler(): void {
            hideAllShownPopovers(doc);
            showPopover(popover_content, updatePositionOfContent);
        },
    };
}

let timeout = 0;

function buildBlurListener(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
): EventListener {
    return {
        element: popover_trigger,
        type: "blur",
        handler(): void {
            hideAllShownPopovers(doc);
            hidePopover(popover_content);
        },
    };
}

function buildMouseOverListener(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
    updatePositionOfContent: () => void,
): EventListener {
    return {
        element: popover_trigger,
        type: "mouseover",
        handler(): void {
            hideAllShownPopovers(doc);
            showPopover(popover_content, updatePositionOfContent);
        },
    };
}

function buildMouseOutListener(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
): EventListener {
    return {
        element: popover_trigger,
        type: "mouseout",
        handler(): void {
            startTimeout(doc, popover_content);
        },
    };
}

function buildMouseLeaveListener(doc: Document, popover_content: HTMLElement): EventListener {
    return {
        element: popover_content,
        type: "mouseleave",
        handler(): void {
            startTimeout(doc, popover_content);
        },
    };
}

function buildMouseEnterListener(doc: Document, popover_content: HTMLElement): EventListener {
    return {
        element: popover_content,
        type: "mouseenter",
        handler(): void {
            stopTimeout();
        },
    };
}

function startTimeout(doc: Document, popover_content: HTMLElement): void {
    timeout = window.setTimeout(() => {
        hideAllShownPopovers(doc);
        hidePopover(popover_content);
    }, TIMEOUT_BEFORE_POPOVER_IS_HIDDEN_ON_MOUSEOUT_IN_MS);
}

function stopTimeout(): void {
    if (timeout) {
        window.clearTimeout(timeout);
        timeout = 0;
    }
}

function buildTriggerClickListener(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
    updatePositionOfContent: () => void,
): EventListener {
    return {
        element: popover_trigger,
        type: "click",
        handler(): void {
            const is_shown = popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME);
            hideAllShownPopovers(doc);
            if (!is_shown) {
                popover_content.setAttribute("x-trigger", "click");
                showPopover(popover_content, updatePositionOfContent);
            }
        },
    };
}

function buildTriggerAccessibleClickListener(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
    updatePositionOfContent: () => void,
): EventListener {
    return {
        element: popover_trigger,
        type: "keydown",
        handler(event: Event): void {
            if (!(event instanceof KeyboardEvent)) {
                return;
            }
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            const is_shown = popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME);
            hideAllShownPopovers(doc);
            if (!is_shown) {
                popover_content.setAttribute("x-trigger", "click");
                showPopover(popover_content, updatePositionOfContent);
            }
        },
    };
}

function buildDocumentClickListener(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
): EventListener {
    return {
        element: doc,
        type: "click",
        handler(event): void {
            if (!(event.target instanceof Element)) {
                return;
            }
            if (event.target.shadowRoot) {
                return;
            }
            if (
                popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME) &&
                !popover_content.contains(event.target) &&
                !popover_trigger.contains(event.target)
            ) {
                hideAllShownPopovers(doc);
            }
        },
    };
}

function buildEscapeListener(doc: Document, popover_content: HTMLElement): EventListener {
    return {
        element: doc,
        type: "keyup",
        handler(event): void {
            if (!(event instanceof KeyboardEvent) || event.key !== "Escape") {
                return;
            }
            if (popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME)) {
                hideAllShownPopovers(doc);
            }
        },
    };
}

function buildDismissClickListener(doc: Document, dismiss: Element): EventListener {
    return {
        element: dismiss,
        type: "click",
        handler(): void {
            hideAllShownPopovers(doc);
        },
    };
}

function hidePopover(popover_content: Element): void {
    popover_content.classList.remove(POPOVER_SHOWN_CLASS_NAME);
    popover_content.dispatchEvent(new CustomEvent(EVENT_TLP_POPOVER_HIDDEN));
}

function hideAllShownPopovers(doc: Document): void {
    doc.dispatchEvent(new CustomEvent(EVENT_POPOVER_FORCE_CLOSE));

    stopTimeout();
}

function showPopover(popover_content: HTMLElement, updatePositionOfContent: () => void): void {
    popover_content.classList.add(POPOVER_SHOWN_CLASS_NAME);
    popover_content.dispatchEvent(new CustomEvent(EVENT_TLP_POPOVER_SHOWN));
    updatePositionOfContent();
}
