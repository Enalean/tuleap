/*
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

import Popper from "popper.js";
import { findClosestElement } from "./dom-walker.js";

export const POPOVER_SHOWN_CLASS_NAME = "tlp-popover-shown";

export function createPopover(doc, popover_trigger, popover_content, options = {}) {
    const anchor = options.anchor || popover_trigger;
    const popper = new Popper(anchor, popover_content, getPopperOptions(anchor, options));

    const dismiss_buttons = popover_content.querySelectorAll('[data-dismiss="popover"]');
    const listeners = buildListeners(
        doc,
        popover_trigger,
        popover_content,
        dismiss_buttons,
        options,
        popper
    );
    attachListeners(listeners);

    const destroy = () => {
        destroyListeners(listeners);
        popper.destroy();
    };

    return {
        destroy,
    };
}

function getPopperOptions(anchor, options) {
    const placement = options.placement || anchor.dataset.placement || "bottom";

    return {
        placement,
        modifiers: {
            arrow: {
                element: ".tlp-popover-arrow",
            },
            computeStyle: {
                gpuAcceleration: false,
            },
        },
    };
}

function buildListeners(doc, popover_trigger, popover_content, dismiss_buttons, options, popper) {
    const trigger = options.trigger || popover_trigger.dataset.trigger || "hover";
    if (trigger === "hover") {
        return [
            buildMouseOverListener(doc, popover_trigger, popover_content, popper),
            buildMouseOutListener(doc, popover_trigger, popover_content),
        ];
    }
    if (trigger === "click") {
        const listeners = [
            buildTriggerClickListener(doc, popover_trigger, popover_content, popper),
            buildDocumentClickListener(doc, popover_trigger, popover_content),
        ];
        for (const dismiss of dismiss_buttons) {
            listeners.push(buildDismissClickListener(doc, dismiss));
        }
        return listeners;
    }

    return [];
}

function attachListeners(listeners) {
    for (const { element, type, handler } of listeners) {
        element.addEventListener(type, handler);
    }
}

function destroyListeners(listeners) {
    for (const { element, type, handler } of listeners) {
        element.removeEventListener(type, handler);
    }
}

function buildMouseOverListener(doc, popover_trigger, popover_content, popper) {
    return {
        element: popover_trigger,
        type: "mouseover",
        handler() {
            hideAllShownPopovers(doc);
            showPopover(popover_content, popper);
        },
    };
}

function buildMouseOutListener(doc, popover_trigger, popover_content) {
    return {
        element: popover_trigger,
        type: "mouseout",
        handler() {
            hideAllShownPopovers(doc);
            popover_content.classList.remove(POPOVER_SHOWN_CLASS_NAME);
        },
    };
}

function buildTriggerClickListener(doc, popover_trigger, popover_content, popper) {
    return {
        element: popover_trigger,
        type: "click",
        handler() {
            const is_shown = popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME);
            hideAllShownPopovers(doc);
            if (!is_shown) {
                popper.popper.setAttribute("x-trigger", "click");
                showPopover(popover_content, popper);
            }
        },
    };
}

function buildDocumentClickListener(doc, popover_trigger, popover_content) {
    return {
        element: doc,
        type: "click",
        handler(event) {
            if (
                popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME) &&
                findClosestElement(doc, event.target, popover_content) === null &&
                findClosestElement(doc, event.target, popover_trigger) === null
            ) {
                hideAllShownPopovers(doc);
            }
        },
    };
}

function buildDismissClickListener(doc, dismiss) {
    return {
        element: dismiss,
        type: "click",
        handler() {
            hideAllShownPopovers(doc);
        },
    };
}

function hideAllShownPopovers(doc) {
    for (const popover of doc.querySelectorAll("." + POPOVER_SHOWN_CLASS_NAME)) {
        popover.classList.remove(POPOVER_SHOWN_CLASS_NAME);
    }
}

function showPopover(popover_content, popper) {
    popper.scheduleUpdate();
    popover_content.classList.add(POPOVER_SHOWN_CLASS_NAME);
}
